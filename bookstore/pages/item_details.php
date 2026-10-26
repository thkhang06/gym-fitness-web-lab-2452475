<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport"
 content="width=device-width, initial-scale=1.0">
 <title>Item Details - Book Store</title>
 <link
 href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
 rel="stylesheet">
 <link rel="stylesheet"
 href="../public/css/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/../components/header.php';
?>

<main class="container py-5">
 <div class="row">
 <div class="col-md-6">
 <img
 src="../public/images/item1.jpg"
 class="img-fluid"
 alt="The Great Gatsby">
 </div>
 <div class="col-md-6">
 <h1>The Great Gatsby</h1>
 <p class="text-muted">
 Category: Classic Literature
 </p>
 <h3>$15</h3>
 <p>
 The Great Gatsby is a 1925 novel by American writer F. Scott Fitzgerald. Set in the Jazz Age on Long Island, near New York City, the novel depicts first-person narrator Nick Carraway's interactions with mysterious millionaire Jay Gatsby and Gatsby's obsession to reunite with his former lover, Daisy Buchanan.
 </p>
 <button
 type="button"
 class="btn btn-primary"
 onclick="showMessage()">
 Add to Cart
 </button>
 </div>
 </div>
</main>

<?php
require_once __DIR__ . '/../components/footer.php';
?>

<script
 src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
<script src="../public/js/script.js"></script>
</body>
</html>
