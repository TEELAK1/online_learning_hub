<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        .faq-question {
            cursor: pointer;
            transition: 0.3s;
        }
        .faq-question:hover {
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <!-- Back to Home Button -->

<!-- Header Section -->
<header class="bg-primary text-white text-center py-5">
    <a href="../index.php" 
           class="btn btn-light text-primary fw-bold px-4 py-2 mb-4 shadow-sm" 
           style="border-radius: 8px;">
            <i class="fas fa-arrow-left me-2"></i> Back to Home
        </a>
    <h1 class="fw-bold">About Online Learning Hub</h1>
    <p class="lead">Empowering Students. Enabling Instructors. Enhancing Learning.</p>
</header>

<!-- Mission Section -->
<section class="container py-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <img src="https://img.freepik.com/free-vector/online-learning-concept-illustration_114360-4761.jpg"
                 alt="Mission" class="img-fluid rounded shadow">
        </div>
        <div class="col-md-6">
            <h2 class="fw-bold mb-3">Our Mission</h2>
            <p class="text-muted">
               Our mission is to provide accessible, high-quality online education to learners across the globe.
We aim to connect passionate instructors with eager students, creating a digital learning experience that is simple, interactive, and effective.
Through modern tools and engaging content, we strive to make education available to everyone—anytime, anywhere.
            </p>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Key Features</h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 border rounded shadow-sm text-center">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Wide Range of Courses</h5>
                    <p class="text-muted">Choose from various subjects taught by expert instructors.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 border rounded shadow-sm text-center">
                    <i class="fas fa-chalkboard-teacher fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Instructor Dashboard</h5>
                    <p class="text-muted">Create courses, upload materials, and manage students easily.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 border rounded shadow-sm text-center">
                    <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Certificates</h5>
                    <p class="text-muted">Get certified after completing courses and assessments.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="container py-5">
    <h2 class="text-center fw-bold mb-5">Meet Our Team Members</h2>
    
    <div class="row g-4 justify-content-center text-center">
        <div class="col-md-4">
            <div class="p-4 border rounded shadow-sm">
                <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" alt="">
                <h5 class="fw-bold">Tilak Neupane, Durgesh Lodh, Dhiraj Kumar Chaudhary, Surendra Shrestha</h5>
                <p class="text-muted">Founder & Developer</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQs Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="fw-bold text-center mb-4">Frequently Asked Questions</h2>

        <div class="accordion" id="faqAccordion">

            <!-- FAQ 1 -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq1">
                        What is Online Learning Hub?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Online Learning Hub is a digital platform where students can learn courses, 
                        attempt quizzes, access study materials, and earn certificates.
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq2">
                        How can I enroll in a course?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Simply create an account, go to the course list, and click the "Enroll" button 
                        for any course you want to study.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq3">
                        Are the certificates free?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes! After completing all lessons and quizzes in a course, you can download 
                        your certificate for free.
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq4">
                        Can instructors upload learning materials?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, instructors have their own dashboard where they can upload PDFs, videos, 
                        quizzes, and assignments.
                    </div>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq5">
                        Is the platform mobile-friendly?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Absolutely! The entire system is built with responsive design to work perfectly 
                        on phones, tablets, and desktops.
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="fw-bold text-center mb-4">Contact Us</h2>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="p-4 border rounded shadow-sm">
                    <p><i class="fas fa-envelope text-primary me-2"></i><strong>Email:</strong> support@learninghub.com</p>
                    <p><i class="fas fa-phone text-primary me-2"></i><strong>Phone:</strong> +977-9864471849</p>
                    <p><i class="fas fa-map-marker-alt text-primary me-2"></i><strong>Location:</strong> Butwal, Nepal</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="text-center py-4 bg-dark text-white">
    &copy; <?php echo date("Y"); ?> Online Learning Hub. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
