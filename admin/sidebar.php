                  
                  
                  <!-- ========== Topbar Start ========== -->

                  <div class="navbar-custom">
                      <div class="topbar container-fluid">
                          <div class="d-flex align-items-center gap-1">


                              <!-- Sidebar Menu Toggle Button -->
                              <button class="button-toggle-menu">
                                  <i class="ri-menu-line"></i>
                              </button>

                              <!-- Horizontal Menu Toggle Button -->
                              <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                                  <div class="lines">
                                      <span></span>
                                      <span></span>
                                      <span></span>
                                  </div>
                              </button>

                          </div>

                          <ul class="topbar-menu d-flex align-items-center gap-3">
                              <!-- menu and search -->
                              <li class="dropdown d-lg-none">
                                  <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                      <i class="ri-search-line fs-22"></i>
                                  </a>
                                  <div class="dropdown-menu dropdown-menu-animated dropdown-lg p-0">
                                      <form class="p-3">
                                          <input type="search" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                                      </form>
                                  </div>
                              </li>


                              <!-- dark mode -->
                              <li class="d-none d-sm-inline-block">
                                  <div class="nav-link" id="light-dark-mode">
                                      <i class="ri-moon-line fs-22"></i>
                                  </div>
                              </li>

                              <!--  profile -->
                              <li class="dropdown">
                                  <a class="nav-link dropdown-toggle arrow-none nav-user" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                      <span class="account-user-avatar">
                                          <img src="assets/images/users/avatar-1.jpg" alt="user-image" width="32" class="rounded-circle">
                                      </span>
                                      <span class="d-lg-block d-none">
                                          <h5 class="my-0 fw-normal">
                                              <?php echo htmlspecialchars($_SESSION['username']); ?>
                                              <i class="ri-arrow-down-s-line d-none d-sm-inline-block align-middle"></i>
                                          </h5>
                                      </span>
                                  </a>
                                  <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                                      <a href="logout.php" class="dropdown-item">
                                          <i class="ri-logout-box-line fs-18 align-middle me-1"></i>
                                          <span>Logout</span>
                                      </a>
                                  </div>
                              </li>
                          </ul>
                      </div>
                  </div>



                  <!-- ========== Left Sidebar Start ========== -->

                  <div class="leftside-menu">

                      <!-- Brand Logo Light -->
                  <br>  
                      <a href="index.php" class="logo logo-light">
                          <span class="logo-lg">
                              <img src="assets/images/logo-light.png" alt="logo">
                          </span>
                      </a>

                      <!-- Brand Logo Dark -->
                      <a href="index.php" class="logo logo-dark">
                          <span class="logo-lg">
                              <img src="assets/images/logo-light.png" alt="dark logo">
                          </span>
                          <span class="logo-sm">
                              <img src="assets/images/logo-light.png" alt="small logo">
                          </span>
                      </a>

                      <!-- Sidebar -left -->
                      <div class="h-100" id="leftside-menu-container" data-simplebar>

                          <!--- Sidemenu -->
                          <ul class="side-nav">
                              <!-- Dashboard -->
                              <li class="side-nav-item">
                                  <a href="index.php" class="side-nav-link">
                                      <i class="ri-dashboard-3-line"></i>
                                      <span> Dashboard </span>
                                  </a>
                              </li>

                              <!-- Events Management -->
                              <li class="side-nav-title">Events Management</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#eventsSubmenu" aria-expanded="false" aria-controls="eventsSubmenu" class="side-nav-link">
                                      <i class="ri-calendar-line"></i>
                                      <span> Events </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="eventsSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="regular_list.php">Regular Events</a>
                                          </li>
                                          <li>
                                              <a href="upcoming_list.php">Upcoming Events</a>
                                          </li>
                                          <li>
                                              <a href="add_event.php">Add Event</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>

                              <!-- Member Management -->
                              <li class="side-nav-title">Member Management</li>
                              <!-- <li class="side-nav-item">
                                  <a href="members_dashboard.php" class="side-nav-link">
                                      <i class="ri-dashboard-line"></i>
                                      <span> Members Dashboard </span>
                                  </a>
                              </li> -->
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#membersSubmenu" aria-expanded="false" aria-controls="membersSubmenu" class="side-nav-link">
                                      <i class="ri-team-line"></i>
                                      <span> Manage Members </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="membersSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="members_list.php">All Members</a>
                                          </li>
                                         <!-- <li>
                                              <a href="add_member.php">Add Member</a>
                                          </li> -->
                                          <li>
                                              <a href="member_approval.php">Approval Workflow</a>
                                          </li>
                                          <li>
                                              <a href="members_bulk_operations.php">Bulk Operations</a>
                                          </li>
                                          <li>
                                              <a href="members_import_export.php">Import/Export</a>
                                          </li>
                                         <!-- <li>
                                              <a href="member_notes.php">Member Notes</a>
                                          </li>-->
                                          <li>
                                              <a href="member_badges.php">Badges</a>
                                          </li>
                                          <li>
                                              <a href="member_permissions.php">Permissions</a>
                                          </li>
                                          <!-- <li>
                                              <a href="member_reports.php">Reports</a>
                                          </li> -->
                                      </ul>
                                  </div>
                              </li>

                              <!-- Resources Management -->
                              <li class="side-nav-title">Resources</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#resourcesSubmenu" aria-expanded="false" aria-controls="resourcesSubmenu" class="side-nav-link">
                                      <i class="ri-file-paper-line"></i>
                                      <span> Resources </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="resourcesSubmenu">
                                      <ul class="side-nav-second-level">
                                          <!-- <li>
                                              <a href="resources_dashboard.php">Dashboard</a>
                                          </li> -->
                                          <li>
                                              <a href="resources_list.php">All Resources</a>
                                          </li>
                                          <!-- <li>
                                              <a href="add_resource.php">Add Resource</a>
                                          </li> -->
                                          <li>
                                              <a href="resource_sections.php">Sections</a>
                                          </li>
                                          <li>
                                              <a href="resource_categories.php">Categories</a>
                                          </li>
                                          <li>
                                              <a href="resources_bulk_operations.php">Bulk Operations</a>
                                          </li>
                                          <li>
                                              <a href="resources_access_control.php">Access Control</a>
                                          </li>
                                          <li>
                                              <a href="resources_analytics.php">Analytics</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>

                              <!-- Research Management -->
                              <li class="side-nav-title">Research</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#researchSubmenu" aria-expanded="false" aria-controls="researchSubmenu" class="side-nav-link">
                                      <i class="ri-search-line"></i>
                                      <span> Research </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="researchSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="research_dashboard.php">Dashboard</a>
                                          </li>
                                          <li>
                                              <a href="research_list.php">All Research</a>
                                          </li>
                                          <!-- <li>
                                              <a href="add_research.php">Add Research</a>
                                          </li> -->
                                          <li>
                                              <a href="research_categories.php">Categories</a>
                                          </li>
                                          <li>
                                              <a href="collaborator.php">Collaborator</a>
                                          </li>
                                          <li>
                                              <a href="research_analytics.php">Analytics</a>
                                          </li>
                                          <li>
                                              <a href="research_versions.php">Version History</a>
                                          </li>
                                          <li>
                                              <a href="research_files.php">Files</a>
                                          </li>
                                          <li>
                                              <a href="research_comments.php">Comments</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>

                              <!-- Digital ID Management -->
                              <li class="side-nav-title">Digital ID</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#digitalIdSubmenu" aria-expanded="false" aria-controls="digitalIdSubmenu" class="side-nav-link">
                                      <i class="ri-id-card-line"></i>
                                      <span> ID Cards </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="digitalIdSubmenu">
                                      <ul class="side-nav-second-level">
                                          <!-- <li>
                                              <a href="digital_id_dashboard.php">ID Cards Dashboard</a>
                                          </li> -->
                                          <li>
                                              <a href="id_cards_list.php">All ID Cards</a>
                                          </li>
                                          <li>
                                              <a href="id_card_generate.php">Generate ID Card</a>
                                          </li>
                                          <li>
                                              <a href="id_card_templates.php">Templates</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>

                              <!-- Reports & Analytics -->
                              <li class="side-nav-title">Reports & Analytics</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#reportsSubmenu" aria-expanded="false" aria-controls="reportsSubmenu" class="side-nav-link">
                                      <i class="ri-bar-chart-2-line"></i>
                                      <span> Reports </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="reportsSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="reports_dashboard.php">Reports Dashboard</a>
                                          </li>
                                          <!-- <li>
                                              <a href="reports_payments.php">Payments</a>
                                          </li> -->
                                          <li>
                                              <a href="reports_research.php">Research</a>
                                          </li>
                                          <li>
                                              <a href="reports_activity.php">Activity</a>
                                          </li>
                                          <!-- <li>
                                              <a href="reports_notes.php">Notes</a>
                                          </li> -->
                                          <li>
                                              <a href="reports_users.php">Users</a>
                                          </li>
                                          <!-- <li>
                                              <a href="reports_members.php">Members</a>
                                          </li> -->
                                          <!-- <li>
                                              <a href="reports_finance.php">Finance</a>
                                          </li> -->
                                          <!-- <li>
                                              <a href="reports_accounting.php">Accounting</a>
                                          </li> -->
                                          <!-- <li>
                                              <a href="reports_details.php">Details</a>
                                          </li> -->
                                          <li>
                                              <a href="report.php">Legacy Reports</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>
                             <!-- <li class="side-nav-item">
                                  <a href="member_analytics.php" class="side-nav-link">
                                      <i class="ri-user-heart-line"></i>
                                      <span> Member Analytics </span>
                                  </a>
                              </li>-->

                              <!-- Communications -->
                         <!--     <li class="side-nav-title">Communications</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#communicationsSubmenu" aria-expanded="false" aria-controls="communicationsSubmenu" class="side-nav-link">
                                      <i class="ri-mail-line"></i>
                                      <span> Communications </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="communicationsSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="send_email.php">Send Email</a>
                                          </li>
                                          <li>
                                              <a href="subscribers_list.php">Email Subscribers</a>
                                          </li>
                                          <li>
                                              <a href="sent_emails_list.php">Sent Emails</a>
                                          </li>
                                          <li>
                                              <a href="chat_dashboard.php">Chat Dashboard</a>
                                          </li>
                                          <li>
                                              <a href="chat_conversations.php">Chat Messages</a>
                                          </li>
                                          <li>
                                              <a href="support_dashboard.php">Support Dashboard</a>
                                          </li>
                                          <li>
                                              <a href="support_tickets_list.php">All Tickets</a>
                                          </li>
                                          <li>
                                              <a href="support_ticket_assignment.php">Ticket Assignment</a>
                                          </li>
                                          <li>
                                              <a href="notifications_center.php">Notifications Dashboard</a>
                                          </li>
                                          <li>
                                              <a href="notifications_list.php">All Notifications</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>-->

                              <!-- Content Management -->
                              <li class="side-nav-title">Content Management</li>
                              <li class="side-nav-item">
                                  <a data-bs-toggle="collapse" href="#contentSubmenu" aria-expanded="false" aria-controls="contentSubmenu" class="side-nav-link">
                                      <i class="ri-folder-line"></i>
                                      <span> Content </span>
                                      <span class="menu-arrow"></span>
                                  </a>
                                  <div class="collapse" id="contentSubmenu">
                                      <ul class="side-nav-second-level">
                                          <li>
                                              <a href="news_list.php">News & Media</a>
                                          </li>
                                          <!-- Temporarily disabled: Add News/Blog from sidebar
                                          <li>
                                              <a href="add_news.php">Add News/Blog</a>
                                          </li>
                                          -->
                                          <li>
                                              <a href="about_team.php">About Page &mdash; Team Members</a>
                                          </li>
                                          <li>
                                              <a href="partners_list.php">Partners</a>
                                          </li>
                                      </ul>
                                  </div>
                              </li>

                              <!-- Access Control -->
                              <li class="side-nav-title">Access Control</li>
                              <li class="side-nav-item">
                                  <a href="membership_packages.php" class="side-nav-link">
                                      <i class="ri-shield-check-line"></i>
                                      <span> Membership Packages </span>
                                  </a>
                              </li>

                              <!-- System -->
                             <!-- <li class="side-nav-title">System</li>
                              <li class="side-nav-item">
                                  <a href="settings.php" class="side-nav-link">
                                      <i class="ri-settings-3-line"></i>
                                      <span> System Settings </span>
                                  </a>
                              </li>-->
                              <li class="side-nav-item">
                                  <a href="settings_users.php" class="side-nav-link">
                                      <i class="ri-user-settings-line"></i>
                                      <span> User Management </span>
                                  </a>
                              </li>
                            <!--  <li class="side-nav-item">
                                  <a href="tools.php" class="side-nav-link">
                                      <i class="ri-tools-line"></i>
                                      <span> Tools & Plugins </span>
                                  </a>
                              </li>-->
                          </ul>
                          <div class="clearfix"></div>
                      </div>
                  </div>