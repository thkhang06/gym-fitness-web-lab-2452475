# Authentication & Session Module (Tasks 4 & 5.1)

This module implements user registration, credential verification, and PHP session state:
- `register.php`: Server-side validated registration form with duplicate email check and bcrypt password hashing.
- `login.php`: Credential authentication via PDO prepared statements, `password_verify()`, and `session_regenerate_id(true)`.
- `logout.php`: Complete session variable clearing, cookie unsetting, and server session destruction.
- `../config/session.php`: Centralized session bootstrap, authentication check (`isLoggedIn()`, `getCurrentUser()`), and protected route guard (`requireLogin()`).
