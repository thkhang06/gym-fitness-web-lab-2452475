<?php
/**
 * Business Logic Service for T1 Topic: Gym & Fitness Center
 * Workflow: Membership -> Fitness Class -> Schedule -> Booking
 * Enforces membership validity, booking quota, duplicate protection, and class capacity limits.
 */

/**
 * Validate YYYY-MM-DD date string strictly
 */
function isValidBookingDate(string $dateStr): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $dt && $dt->format('Y-m-d') === $dateStr;
}

/**
 * Fetch user's active membership subscription along with active booking count
 */
function getUserActiveMembership(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            um.id AS membership_id,
            um.user_id,
            um.plan_id,
            um.status,
            um.start_date,
            um.end_date,
            mp.name AS plan_name,
            mp.tier_code,
            mp.price AS plan_price,
            mp.duration_days,
            mp.max_active_bookings,
            mp.description AS plan_description
        FROM user_memberships um
        INNER JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.user_id = :user_id
          AND um.status = 'active'
          AND um.end_date >= CURDATE()
        ORDER BY um.end_date DESC, um.id DESC
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $userId]);
    $membership = $stmt->fetch();

    if (!$membership) {
        return null;
    }

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM class_bookings
        WHERE user_id = :user_id
          AND status = 'confirmed'
          AND booking_date >= CURDATE()
    ");
    $countStmt->execute(['user_id' => $userId]);
    $activeBookings = (int)$countStmt->fetchColumn();

    $maxQuota = (int)$membership['max_active_bookings'];
    $membership['active_bookings_count'] = $activeBookings;
    $membership['remaining_quota'] = max(0, $maxQuota - $activeBookings);
    $membership['quota_reached'] = ($activeBookings >= $maxQuota);

    return $membership;
}

/**
 * Compute live capacity snapshot for a fitness class on a given date
 */
function getClassCapacitySnapshot(PDO $pdo, int $classId, string $bookingDate, ?int $userId = null): ?array {
    if (!isValidBookingDate($bookingDate)) {
        $bookingDate = date('Y-m-d', strtotime('+1 day'));
    }

    $stmt = $pdo->prepare("
        SELECT
            fc.id,
            fc.title,
            fc.trainer_name,
            fc.schedule_info,
            fc.price,
            fc.max_capacity,
            c.name AS category_name
        FROM fitness_classes fc
        INNER JOIN categories c ON fc.category_id = c.id
        WHERE fc.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $classId]);
    $class = $stmt->fetch();

    if (!$class) {
        return null;
    }

    $bookedStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM class_bookings
        WHERE class_id = :class_id
          AND booking_date = :booking_date
          AND status = 'confirmed'
    ");
    $bookedStmt->execute([
        'class_id'     => $classId,
        'booking_date' => $bookingDate,
    ]);
    $bookedCount = (int)$bookedStmt->fetchColumn();

    $maxCapacity = max(1, (int)$class['max_capacity']);
    $remaining = max(0, $maxCapacity - $bookedCount);
    $fillPercent = (int)min(100, round(($bookedCount / $maxCapacity) * 100));

    $userBooked = false;
    $userBookingId = null;
    if ($userId !== null && $userId > 0) {
        $uStmt = $pdo->prepare("
            SELECT id
            FROM class_bookings
            WHERE class_id = :class_id
              AND user_id = :user_id
              AND booking_date = :booking_date
              AND status = 'confirmed'
            LIMIT 1
        ");
        $uStmt->execute([
            'class_id'     => $classId,
            'user_id'      => $userId,
            'booking_date' => $bookingDate,
        ]);
        $existingId = $uStmt->fetchColumn();
        if ($existingId) {
            $userBooked = true;
            $userBookingId = (int)$existingId;
        }
    }

    return [
        'class_id'            => (int)$class['id'],
        'title'               => $class['title'],
        'category_name'       => $class['category_name'],
        'trainer_name'        => $class['trainer_name'],
        'schedule_info'       => $class['schedule_info'],
        'price'               => (float)$class['price'],
        'booking_date'        => $bookingDate,
        'max_capacity'        => $maxCapacity,
        'booked_count'        => $bookedCount,
        'remaining_spots'     => $remaining,
        'fill_percent'        => $fillPercent,
        'is_full'             => ($bookedCount >= $maxCapacity),
        'user_already_booked' => $userBooked,
        'user_booking_id'     => $userBookingId,
    ];
}

/**
 * Activate or switch a user's membership plan
 */
function activateUserMembership(PDO $pdo, int $userId, int $planId): array {
    $planStmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = :id LIMIT 1");
    $planStmt->execute(['id' => $planId]);
    $plan = $planStmt->fetch();

    if (!$plan) {
        return ['ok' => false, 'code' => 'PLAN_NOT_FOUND', 'message' => 'Selected membership tier does not exist.'];
    }

    try {
        $pdo->beginTransaction();

        // Cancel existing active plans before issuing new plan
        $cancelStmt = $pdo->prepare("
            UPDATE user_memberships
            SET status = 'cancelled'
            WHERE user_id = :user_id AND status = 'active'
        ");
        $cancelStmt->execute(['user_id' => $userId]);

        $startDate = date('Y-m-d');
        $duration = max(1, (int)$plan['duration_days']);
        $endDate = date('Y-m-d', strtotime("+{$duration} days"));

        $insStmt = $pdo->prepare("
            INSERT INTO user_memberships (user_id, plan_id, status, start_date, end_date, created_at)
            VALUES (:user_id, :plan_id, 'active', :start_date, :end_date, NOW())
        ");
        $insStmt->execute([
            'user_id'    => $userId,
            'plan_id'    => $planId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        $pdo->commit();
        return [
            'ok'         => true,
            'message'    => "Membership '{$plan['name']}' activated successfully (valid until {$endDate}).",
            'membership' => getUserActiveMembership($pdo, $userId),
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Membership activation error: ' . $e->getMessage());
        return ['ok' => false, 'code' => 'DB_ERROR', 'message' => 'Could not activate membership. Please try again.'];
    }
}

/**
 * Execute T1 Booking Transaction:
 * Membership check -> Quota check -> Duplicate check -> Capacity check (FOR UPDATE lock) -> Insert
 */
function createClassBooking(PDO $pdo, int $userId, int $classId, string $bookingDate, string $notes = ''): array {
    if (!isValidBookingDate($bookingDate)) {
        return ['ok' => false, 'code' => 'INVALID_DATE', 'message' => 'Please provide a valid booking date (YYYY-MM-DD).'];
    }

    $today = date('Y-m-d');
    $maxAdvance = date('Y-m-d', strtotime('+60 days'));
    if ($bookingDate < $today) {
        return ['ok' => false, 'code' => 'PAST_DATE', 'message' => 'Cannot book a training class in the past.'];
    }
    if ($bookingDate > $maxAdvance) {
        return ['ok' => false, 'code' => 'DATE_TOO_FAR', 'message' => 'Bookings can only be scheduled up to 60 days in advance.'];
    }

    $cleanNotes = mb_substr(trim($notes), 0, 255);

    try {
        $pdo->beginTransaction();

        // Step 1: Lock target fitness class row to avoid race conditions on capacity
        $classStmt = $pdo->prepare("SELECT id, title, max_capacity FROM fitness_classes WHERE id = :id FOR UPDATE");
        $classStmt->execute(['id' => $classId]);
        $classRow = $classStmt->fetch();

        if (!$classRow) {
            $pdo->rollBack();
            return ['ok' => false, 'code' => 'CLASS_NOT_FOUND', 'message' => 'Selected fitness class does not exist.'];
        }

        // Step 2: Verify Active Membership (T1 Workflow Step 1)
        $membership = getUserActiveMembership($pdo, $userId);
        if (!$membership) {
            $pdo->rollBack();
            return [
                'ok'      => false,
                'code'    => 'NO_ACTIVE_MEMBERSHIP',
                'message' => 'An active Membership Plan is required before booking a fitness class. Please activate a membership plan first.'
            ];
        }

        // Step 3: Verify Membership Active Booking Quota
        if ($membership['quota_reached']) {
            $pdo->rollBack();
            return [
                'ok'      => false,
                'code'    => 'MEMBERSHIP_QUOTA_REACHED',
                'message' => "Your '{$membership['plan_name']}' plan allows up to {$membership['max_active_bookings']} active bookings. Cancel an existing booking or upgrade to VIP."
            ];
        }

        // Step 4: Prevent Duplicate Booking by same member for same class & date
        $dupStmt = $pdo->prepare("
            SELECT id FROM class_bookings
            WHERE user_id = :user_id
              AND class_id = :class_id
              AND booking_date = :booking_date
              AND status = 'confirmed'
            LIMIT 1
        ");
        $dupStmt->execute([
            'user_id'      => $userId,
            'class_id'     => $classId,
            'booking_date' => $bookingDate,
        ]);
        if ($dupStmt->fetch()) {
            $pdo->rollBack();
            return [
                'ok'      => false,
                'code'    => 'DUPLICATE_BOOKING',
                'message' => "You already have a confirmed booking for '{$classRow['title']}' on {$bookingDate}."
            ];
        }

        // Step 5: Enforce Mandatory Class Capacity Limit
        $capStmt = $pdo->prepare("
            SELECT COUNT(*) FROM class_bookings
            WHERE class_id = :class_id
              AND booking_date = :booking_date
              AND status = 'confirmed'
        ");
        $capStmt->execute([
            'class_id'     => $classId,
            'booking_date' => $bookingDate,
        ]);
        $bookedCount = (int)$capStmt->fetchColumn();
        $maxCap = max(1, (int)$classRow['max_capacity']);

        if ($bookedCount >= $maxCap) {
            $pdo->rollBack();
            return [
                'ok'       => false,
                'code'     => 'CAPACITY_FULL',
                'message'  => "Booking blocked: '{$classRow['title']}' on {$bookingDate} has reached maximum capacity ({$bookedCount}/{$maxCap} spots filled).",
                'capacity' => getClassCapacitySnapshot($pdo, $classId, $bookingDate, $userId),
            ];
        }

        // Step 6: Insert confirmed booking
        $insStmt = $pdo->prepare("
            INSERT INTO class_bookings (user_id, class_id, membership_id, booking_date, status, notes, created_at)
            VALUES (:user_id, :class_id, :membership_id, :booking_date, 'confirmed', :notes, NOW())
        ");
        $insStmt->execute([
            'user_id'       => $userId,
            'class_id'      => $classId,
            'membership_id' => (int)$membership['membership_id'],
            'booking_date'  => $bookingDate,
            'notes'         => $cleanNotes,
        ]);
        $newId = (int)$pdo->lastInsertId();

        $pdo->commit();

        return [
            'ok'         => true,
            'booking_id' => $newId,
            'message'    => "Booking #{$newId} confirmed for '{$classRow['title']}' on {$bookingDate}!",
            'capacity'   => getClassCapacitySnapshot($pdo, $classId, $bookingDate, $userId),
            'membership' => getUserActiveMembership($pdo, $userId),
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Booking creation error: ' . $e->getMessage());
        return ['ok' => false, 'code' => 'DB_ERROR', 'message' => 'Failed to create class booking due to a system error.'];
    }
}

/**
 * Update booking status with RBAC and capacity verification
 */
function updateBookingStatus(PDO $pdo, int $bookingId, string $newStatus, array $actorUser): array {
    $allowedStatuses = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        return ['ok' => false, 'code' => 'INVALID_STATUS', 'message' => 'Invalid booking status value.'];
    }

    $isStaff = in_array($actorUser['role'] ?? '', ['admin', 'trainer'], true);
    $actorId = (int)($actorUser['id'] ?? 0);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT cb.*, fc.title AS class_title, fc.max_capacity
            FROM class_bookings cb
            INNER JOIN fitness_classes fc ON cb.class_id = fc.id
            WHERE cb.id = :id
            FOR UPDATE
        ");
        $stmt->execute(['id' => $bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $pdo->rollBack();
            return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Booking record not found.'];
        }

        // Authorization check
        if (!$isStaff) {
            if ((int)$booking['user_id'] !== $actorId) {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'FORBIDDEN', 'message' => 'You are not authorized to modify another member\'s booking.'];
            }
            if ($newStatus !== 'cancelled') {
                $pdo->rollBack();
                return ['ok' => false, 'code' => 'FORBIDDEN', 'message' => 'Members may only cancel their own confirmed bookings.'];
            }
        }

        // If re-confirming a previously cancelled booking, enforce capacity rule again
        if ($newStatus === 'confirmed' && $booking['status'] !== 'confirmed') {
            $capStmt = $pdo->prepare("
                SELECT COUNT(*) FROM class_bookings
                WHERE class_id = :class_id AND booking_date = :booking_date AND status = 'confirmed'
            ");
            $capStmt->execute([
                'class_id'     => (int)$booking['class_id'],
                'booking_date' => $booking['booking_date'],
            ]);
            $bookedCount = (int)$capStmt->fetchColumn();
            if ($bookedCount >= (int)$booking['max_capacity']) {
                $pdo->rollBack();
                return [
                    'ok'      => false,
                    'code'    => 'CAPACITY_FULL',
                    'message' => "Cannot re-confirm booking #{$bookingId}: class capacity ({$bookedCount}/{$booking['max_capacity']}) is already full."
                ];
            }
        }

        $updStmt = $pdo->prepare("UPDATE class_bookings SET status = :status WHERE id = :id");
        $updStmt->execute([
            'status' => $newStatus,
            'id'     => $bookingId,
        ]);

        $pdo->commit();
        return [
            'ok'       => true,
            'message'  => "Booking #{$bookingId} status updated to '" . strtoupper($newStatus) . "'.",
            'capacity' => getClassCapacitySnapshot($pdo, (int)$booking['class_id'], $booking['booking_date'], $actorId),
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Booking status update error: ' . $e->getMessage());
        return ['ok' => false, 'code' => 'DB_ERROR', 'message' => 'Database error while updating booking status.'];
    }
}
