<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Online Book Store</title>
    <link rel="stylesheet" href="./bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="./bootstrap/css/fontawesome.min.css">
</head>
<body>
    <?php include './template/header.php'; ?>

    <div class="container mt-5">
        <h2>Contact Us</h2>
        <p>If you have any questions or need assistance, please fill out the form below and we'll get back to you as soon as possible.</p>

        <form action="#" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <?php include './template/footer.php'; ?>
    <script src="./bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
