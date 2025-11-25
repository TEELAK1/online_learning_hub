<?php
// Student/download_certificate.php
// Renders a print-ready A4-landscape certificate for a given certificate code

session_start();
require_once '../config/database.php';

$db = getDB();

// Accept certificate code via GET (e.g. ?code=CERT-ABC123)
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

$cert = null;
if ($code) {
    $stmt = $db->prepare(
        "SELECT cert.certificate_code, cert.issued_at, cert.status,
                s.student_id, s.name as student_name,
                c.course_id, c.title as course_title,
                i.name as instructor_name
         FROM certificates cert
         LEFT JOIN student s ON cert.student_id = s.student_id
         LEFT JOIN courses c ON cert.course_id = c.course_id
         LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
         WHERE cert.certificate_code = ? LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $cert = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

// If not found, allow preview with sample data (for instructors only)
$preview = false;
if (!$cert) {
    // If user is instructor allow preview mode; otherwise show error
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor') {
        $preview = true;
        $cert = [
            'certificate_code' => 'PREVIEW-CERT-0001',
            'issued_at' => date('Y-m-d H:i:s'),
            'status' => 'preview',
            'student_name' => 'Student Name',
            'course_title' => 'Course Title',
            'instructor_name' => 'Instructor Name',
        ];
    } else {
        http_response_code(404);
        echo "<h2>Certificate not found</h2><p>The certificate code is invalid or missing.</p>";
        exit();
    }
}

// Prepare printable HTML certificate (A4 landscape)
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Certificate - <?php echo htmlspecialchars($cert['certificate_code']); ?></title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <style>
    /* Page setup for A4 landscape */
    @page { size: A4 landscape; margin: 0; }
    html, body { height: 100%; margin: 0; }

    body {
      background: #f0f4f8; /* subtle page background */
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      -webkit-print-color-adjust: exact;
    }

    .certificate-wrapper {
      width: 297mm; /* A4 landscape */
      height: 210mm;
      background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
      border: 18px solid #0b2340; /* navy outer border */
      box-sizing: border-box;
      position: relative;
      box-shadow: 0 10px 30px rgba(11,35,64,0.35);
      font-family: 'Poppins', sans-serif;
    }

    .inner-border {
      position: absolute;
      inset: 18px;
      border: 6px solid rgba(212,175,55,0.92); /* gold inner border */
      box-sizing: border-box;
      padding: 28px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      overflow: visible;
    }

    .certificate-body {
      width: 100%;
      height: 100%;
      text-align: center;
      color: #0b2340; /* navy text */
      position: relative;
    }

    .ornament-top {
      position: absolute;
      top: 8px;
      left: 50%;
      transform: translateX(-50%);
      width: 80%;
      height: 36px;
      background: linear-gradient(90deg, rgba(11,35,64,0.06), rgba(11,35,64,0.0));
      border-radius: 8px;
    }

    h1.certificate-title {
      font-family: 'Playfair Display', serif;
      font-size: 42px;
      letter-spacing: 2px;
      margin: 0 0 10px 0;
      color: #07223a;
      font-weight: 700;
    }

    .subtitle {
      font-size: 14px;
      color: #274b6b;
      margin-bottom: 26px;
    }

    /* Bigger recipient name as requested */
    .recipient {
      font-family: 'Playfair Display', serif;
      font-size: 56px;
      font-weight: 700;
      color: #0b2340;
      margin: 8px 0 6px 0;
    }

    .award-text {
      font-size: 18px;
      color: #274b6b;
      margin-bottom: 14px;
    }

    /* Larger, bolder course title */
    .course-title {
      font-family: 'Playfair Display', serif;
      font-size: 34px;
      color: #0b2340;
      margin: 10px 0 10px 0;
      font-weight: 800;
    }

    /* Centered, larger completed date */
    .completed-date {
      font-size: 18px;
      color: #0b2340;
      margin-top: 6px;
      font-weight: 600;
      text-align: center;
    }

    .meta-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-top: 28px;
      padding: 0 40px;
    }

    .instructor {
      text-align: left;
    }

    .instructor .label {
      font-size: 12px;
      color: #274b6b;
      margin-bottom: 6px;
    }

    /* Make instructor name bolder and a bit larger */
    .instructor .name {
      font-size: 18px;
      font-weight: 700;
      color: #0b2340;
    }

    .signature {
      margin-top: 18px;
      border-top: 1px solid #cbb77a;
      width: 220px;
    }

    .issuer {
      text-align: right;
    }

    .issuer .project {
      font-size: 14px;
      color: #274b6b;
      margin-bottom: 8px;
    }

    .issued-at {
      font-size: 12px;
      color: #274b6b;
    }

    /* Keep bottom cert-id as well, and add a top cert id style */
    .cert-id {
      position: absolute;
      bottom: 18px;
      left: 40px;
      font-size: 12px;
      color: #274b6b;
    }

    .cert-id-top {
      position: absolute;
      top: 36px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 13px;
      color: #274b6b;
      font-weight: 600;
    }

    /* Gold embossed seal */
    .seal {
      position: absolute;
      right: 48px;
      bottom: 38px;
      width: 120px;
      height: 120px;
      border-radius: 999px;
      background: radial-gradient(circle at 30% 30%, #f7e9c9 0%, #d4a83a 18%, #b07a20 45%, #8a5c14 100%);
      box-shadow: 0 6px 18px rgba(136, 95, 0, 0.35), inset 0 3px 8px rgba(255,255,255,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-family: 'Playfair Display', serif;
      font-size: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .logo-placeholder {
      position: absolute;
      left: 40px;
      top: 40px;
      width: 120px;
      height: 60px;
      background: linear-gradient(90deg, rgba(11,35,64,0.08), rgba(11,35,64,0.02));
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      color: #07223a;
      font-weight: 700;
      font-size: 12px;
    }

    /* Subtle watermark behind the content: SVG (graduation cap + text) */
    .inner-border::before {
      content: "";
      position: absolute;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      opacity: 0.1; /* very faint */
      transform: rotate(-18deg);
      background-repeat: no-repeat;
      background-position: center;
      background-size: 150% auto;
      /* Inline SVG data URL: small graduation cap + centered text */
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 300'><g fill='%230b2340' fill-opacity='1'><path d='M600 40 L860 120 L600 200 L340 120 Z'/><path d='M860 120 L900 110 L900 130 Z'/></g><text x='600' y='220' font-family='Playfair Display, serif' font-size='72' fill='%230b2340' text-anchor='middle'>Online%20Learning%20Hub</text></svg>");
    }

    /* Ensure certificate content sits above watermark */
    .inner-border > * {
      position: relative;
      z-index: 1;
    }

    /* Slightly reduce watermark when printing to keep it very subtle */
    @media print {
      .inner-border::before { opacity: 0.04; }
    }

    /* Print button */
    .controls {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }

    .btn-primary-print {
      background: #0b2340;
      color: #fff;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      font-weight: 600;
      box-shadow: 0 6px 14px rgba(11,35,64,0.18);
      cursor: pointer;
    }

    /* Hide controls when printing */
    @media print {
      .controls { display: none; }
      body { background: white; }
      .certificate-wrapper { box-shadow: none; margin: 0; }
    }
  </style>
</head>
<body>

  <div class="controls">
    <button class="btn-primary-print" onclick="window.print()">Download / Print</button>
  </div>

  <div class="certificate-wrapper" role="document" aria-label="Certificate of Completion">
    <div class="inner-border">
      <div class="certificate-body">
 <div class="ornament-top"></div>
 <div style="margin-top:18px;">
          <h1 class="certificate-title">Certificate of Completion</h1>
          <div class="subtitle">This certificate is awarded to</div>
        <div class="recipient"><?php echo htmlspecialchars($cert['student_name']); ?></div>
  <div class="award-text">for successfully completing the course</div>
          <div class="course-title"><?php echo htmlspecialchars($cert['course_title']); ?></div>
          <div class="completed-date">Completed: <?php echo date('F j, Y', strtotime($cert['issued_at'])); ?></div>
        </div>
<div class="meta-row">
          <div class="instructor">
            <div class="label">Instructor</div>
            <div class="name"><?php echo htmlspecialchars($cert['instructor_name']); ?></div>
            <div class="signature" aria-hidden="true"></div>
          </div>

          <div class="issuer">
            <!-- Issuer/project area intentionally left minimal to keep focus on centered date -->
          </div>
        </div>
<div class="cert-id">Certificate ID: <?php echo htmlspecialchars($cert['certificate_code']); ?></div>
        <div class="seal"></div>
 </div>
    </div>
  </div>

</body>
</html>
