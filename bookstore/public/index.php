<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport"
 content="width=device-width, initial-scale=1.0">
 <title>Book Store</title>
 <link
 href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
 rel="stylesheet">
 <link rel="stylesheet"
 href="css/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/../components/header.php';
?>

<section class="hero-section py-5 bg-light">
 <div class="container text-center">
 <h1>Welcome to Book Store</h1>
 <p class="lead">
 Discover your next favorite book.
 </p>
 <a href="#items" class="btn btn-primary">
 Explore Items
 </a>
 </div>
</section>

<section class="py-5" id="items">
 <div class="container">
 <h2 class="text-center mb-4">
 Featured Items
 </h2>
 <div class="row g-4">
 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item1.jpg"
 class="card-img-top"
 alt="The Great Gatsby">
 <div class="card-body">
 <h5 class="card-title">
 The Great Gatsby
 </h5>
 <p class="card-text">
 A classic novel exploring wealth, love, and tragedy in the American Jazz Age.
 </p>
 <p class="fw-bold">
 $15
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>

 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item2.jpg"
 class="card-img-top"
 alt="To Kill a Mockingbird">
 <div class="card-body">
 <h5 class="card-title">
 To Kill a Mockingbird
 </h5>
 <p class="card-text">
 A gripping story of racial injustice and the destruction of innocence.
 </p>
 <p class="fw-bold">
 $18
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>

 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item3.jpg"
 class="card-img-top"
 alt="1984">
 <div class="card-body">
 <h5 class="card-title">
 1984
 </h5>
 <p class="card-text">
 George Orwell's chilling prophecy about totalitarianism and mass surveillance.
 </p>
 <p class="fw-bold">
 $14
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>

 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item4.jpg"
 class="card-img-top"
 alt="Pride and Prejudice">
 <div class="card-body">
 <h5 class="card-title">
 Pride and Prejudice
 </h5>
 <p class="card-text">
 Jane Austen's romantic classic depicting the battle of wit between Elizabeth and Darcy.
 </p>
 <p class="fw-bold">
 $12
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>

 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item5.jpg"
 class="card-img-top"
 alt="The Catcher in the Rye">
 <div class="card-body">
 <h5 class="card-title">
 The Catcher in the Rye
 </h5>
 <p class="card-text">
 J.D. Salinger's moving novel about teenage alienation and the search for identity.
 </p>
 <p class="fw-bold">
 $16
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>

 <div class="col-12 col-md-6 col-lg-4">
 <div class="card h-100">
 <img
 src="images/item6.jpg"
 class="card-img-top"
 alt="The Hobbit">
 <div class="card-body">
 <h5 class="card-title">
 The Hobbit
 </h5>
 <p class="card-text">
 J.R.R. Tolkien's timeless adventure of Bilbo Baggins and the quest for Erebor.
 </p>
 <p class="fw-bold">
 $20
 </p>
 <a href="../pages/item_details.php"
 class="btn btn-primary">
 View Details
 </a>
 </div>
 </div>
 </div>
 </div>
 </div>
</section>

<?php
require_once __DIR__ . '/../components/footer.php';
?>

<script
 src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
<script src="js/script.js"></script>
</body>
</html>
