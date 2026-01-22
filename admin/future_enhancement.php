<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}
include 'header.php';
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="header-title">Future Enhancements & Strategic Roadmap</h4>
                                <p class="text-muted mb-0">
                                    A strategic outline of potential features to support the growth and mission of the ESWPA.
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="enhancementsAccordion">

                                    <!-- Category 1 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingOne">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                <strong>Category 1: Revenue Generation & Strategic Growth</strong>
                                            </button>
                                        </h2>
                                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#enhancementsAccordion">
                                            <div class="accordion-body">
                                                <p><em>Features focused on creating diverse revenue streams, strengthening partnerships, and ensuring long-term financial sustainability.</em></p>
                                                <ul class="list-group">
                                                    <li class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h5 class="mb-1">Tiered Membership & Corporate Packages</h5>
                                                            <span class="badge bg-success rounded-pill">High Priority</span>
                                                        </div>
                                                        <p class="mb-1"><strong>Description:</strong> Evolve the single membership into multiple tiers (e.g., Student, Professional, Institutional).</p>
                                                        <small><strong>Strategy:</strong> Each tier offers increasing benefits and price points. Institutional packages can be sold to hospitals, universities, and NGOs, creating a significant B2B revenue stream.</small>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h5 class="mb-1">Paid Workshops & Event Ticketing</h5>
                                                            <span class="badge bg-success rounded-pill">High Priority</span>
                                                        </div>
                                                        <p class="mb-1"><strong>Description:</strong> An integrated system to create, promote, and sell tickets for paid workshops, webinars, and annual conferences.</p>
                                                        <small><strong>Strategy:</strong> Generates direct revenue from attendance. Offering member-exclusive discounts drives membership growth, as the savings on events can pay for the membership itself.</small>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h5 class="mb-1">Donation & Fundraising Campaigns</h5>
                                                            <span class="badge bg-success rounded-pill">High Priority</span>
                                                        </div>
                                                        <p class="mb-1"><strong>Description:</strong> A dedicated page for collecting online donations and running specific fundraising campaigns (e.g., "Sponsor a research paper").</p>
                                                        <small><strong>Strategy:</strong> Taps into public and corporate goodwill, providing an essential revenue stream. Clear campaigns with progress bars increase donor engagement.</small>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h5 class="mb-1">Professional Job Board & Listings</h5>
                                                            <span class="badge bg-warning rounded-pill">Medium Priority</span>
                                                        </div>
                                                        <p class="mb-1"><strong>Description:</strong> A dedicated job board where partner organizations pay a fee to list vacancies for social workers.</p>
                                                        <small><strong>Strategy:</strong> Creates a new, consistent revenue stream from employers. Free browsing for members acts as a major membership benefit.</small>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Category 2 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingTwo">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                <strong>Category 2: Member & Professional Development</strong>
                                            </button>
                                        </h2>
                                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#enhancementsAccordion">
                                            <div class="accordion-body">
                                               <p><em>Features to enhance the skills, knowledge, and career opportunities for members.</em></p>
                                                <ul class="list-group">
                                                     <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Resource Hub / Digital Library</div>
                                                            A secure, members-only repository for professional documents, research papers, training materials, and ESWPA-developed standards.
                                                        </div>
                                                        <span class="badge bg-success rounded-pill">High Priority</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Job Board</div>
                                                            A feature where ESWPA and its partner organizations can post job vacancies for social workers.
                                                        </div>
                                                        <span class="badge bg-success rounded-pill">High Priority</span>
                                                    </li>
                                                     <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Training & Certification Tracker</div>
                                                           A module for members to track their professional development units (PDUs) or certifications.
                                                        </div>
                                                        <span class="badge bg-warning rounded-pill">Medium Priority</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                     <!-- Category 3 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingThree">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                <strong>Category 3: Community Engagement & Crisis Response</strong>
                                            </button>
                                        </h2>
                                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#enhancementsAccordion">
                                            <div class="accordion-body">
                                                <p><em>Tools to foster community and enable rapid response.</em></p>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Member Forum/Discussion Board</div>
                                                            A private forum for members to discuss professional topics, ask questions, and share best practices.
                                                        </div>
                                                        <span class="badge bg-warning rounded-pill">Medium Priority</span>
                                                    </li>
                                                     <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Crisis Response Module</div>
                                                            A dedicated section for publishing alerts, coordinating volunteer efforts, and disseminating critical information during crises.
                                                        </div>
                                                        <span class="badge bg-warning rounded-pill">Medium Priority</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Category 4 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingFour">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                                <strong>Category 4: Administration & Operations</strong>
                                            </button>
                                        </h2>
                                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#enhancementsAccordion">
                                            <div class="accordion-body">
                                                <p><em>Features to improve the efficiency of ESWPA's internal operations.</em></p>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Automated Backup System</div>
                                                           Implement a daily or weekly automated backup script that saves the database and user-uploaded files to a secure off-site location.
                                                        </div>
                                                        <span class="badge bg-success rounded-pill">High Priority</span>
                                                    </li>
                                                     <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">HR / Volunteer Management</div>
                                                           A simple module to manage internal volunteers, including registration, role assignment, and tracking hours.
                                                        </div>
                                                        <span class="badge bg-warning rounded-pill">Medium Priority</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold">Activity Log</div>
                                                           A system that logs all major actions taken by admins. Crucial for security and accountability.
                                                        </div>
                                                        <span class="badge bg-danger rounded-pill">Low Priority</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html> 