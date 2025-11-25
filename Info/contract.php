<?php
$host = "localhost:3306";
$user = "root";
$pass = "";
$db = "onlinelearninghub_new";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message_sent = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        // Prepared statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $message);

            if ($stmt->execute()) {
                $message_sent = true;
            } else {
                $error_message = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Prepare failed: " . $conn->error;
        }
    } else {
        $error_message = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - Online Learning Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      padding-top: 70px;
    }
    .hero {
      background: linear-gradient(135deg, #007bff, #6610f2);
      color: white;
      padding: 80px 0;
      text-align: center;
    }
    .contact-card {
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .contact-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="../index.php">OnlineLearningHub</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="../Courses/courses.php">Courses</a></li>
          <li class="nav-item"><a class="nav-link" href="../Functionality/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="../Functionality/register.php">Register</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Header -->
  <section class="hero">
    <div class="container">
      <h1>Contact Us</h1>
      <p class="lead">We would love to hear from you!</p>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="py-5 bg-light">
    <div class="container">
      <?php if ($message_sent): ?>
        <div class="alert alert-success text-center">
          ✅ Your message has been sent successfully! We will get back to you soon.
        </div>
      <?php elseif ($error_message): ?>
        <div class="alert alert-danger text-center">
          ❌ <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <div class="row justify-content-center g-4">
        <!-- Contact Form -->
        <div class="col-md-6">
          <div class="card p-4 shadow-sm contact-card">
            <form action="" method="post">
              <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">Send Message</button>
            </form>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="col-md-4">
          <div class="p-4 contact-card">
            <h5>Get in Touch</h5>
            <p><i class="fa-solid fa-envelope me-2"></i> support@OLHsupport@gmail.com</p>
            <p><i class="fa-solid fa-phone me-2"></i> +977 9864471849</p>
            <p><i class="fa-solid fa-map-marker-alt me-2"></i> Butwal, Nepal</p>
            <h6>Follow Us</h6>
            <a href="#" class="me-2 text-dark"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="me-2 text-dark"><i class="fa-brands fa-twitter"></i></a>
            <a href="#" class="text-dark"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark text-light py-4">
    <div class="container text-center">
      <p>&copy; 2025 Online Learning Hub. All rights reserved.</p>
      <p>
        <a href="../Info/privacy_policy.php" class="text-light me-2">Privacy Policy</a> |
        <a href="../Info/contract.php" class="text-light ms-2">Contact</a>
      </p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
