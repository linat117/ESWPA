# Future Enhancements and Implementation Plan

This document outlines potential future features, enhancements, and updates to improve the Ethio Social Works admin panel and overall application. Each item is given a RAG (Red, Amber, Green) rating to help with prioritization.

- **Green:** High priority, high impact, relatively low effort.
- **Amber:** Medium priority, good impact, moderate effort.
- **Red:** Lower priority, nice-to-have, potentially high effort.

---

### 1. Enhanced Member Management

| Feature                       | Description                                                                                                                                                             | RAG Rating |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- |
| **Edit Member Details**       | Add an "Edit" button on the `members_list.php` page to allow admins to update a member's information directly, without needing database access.                               | 🟢 Green    |
| **Manual Subscription Renewal** | Add a feature for admins to manually extend a member's subscription. This could be useful if a member pays in person or through a method not integrated with the site.     | 🟢 Green    |
| **Membership Status Filter**  | Add filters to the `members_list.php` page to quickly view "Active," "Expired," or "Soon to Expire" members.                                                              | 🟡 Amber   |
| **Member Note-Taking**        | Add a section in each member's profile (perhaps in the CV modal) for admins to leave private notes (e.g., "Followed up on payment," "Special request approved").             | 🟡 Amber   |

### 2. Content Management System (CMS)

| Feature                       | Description                                                                                                                                                  | RAG Rating |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------- |
| **Editable "About Us" Page**  | Create an admin interface to edit the text and images on the `about.php` page, including managing the team members list (add, edit, delete, re-order).         | 🟡 Amber   |
| **Editable "Contact" Page**   | Allow admins to update contact information (address, phone, email) displayed on the `contact.php` page.                                                      | 🟢 Green    |
| **Frontend Content Editor**   | Implement a simple CMS for other key text sections on the main site (e.g., the homepage welcome message) so they can be changed without editing PHP files.     | 🔴 Red     |

### 3. Advanced Reporting & Analytics

| Feature                       | Description                                                                                                                                                              | RAG Rating |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------- |
| **Export Reports to CSV/PDF** | Add "Export" buttons to the `report.php` datatables to allow admins to download filtered reports as CSV or PDF files for offline analysis or record-keeping.                | 🟡 Amber   |
| **Email Analytics**           | Track email open and click rates for the bulk emails sent. This would provide insight into how engaging the newsletters are. (Requires a third-party email service). | 🔴 Red     |
| **Member Demographics Chart** | Add a new chart to the dashboard showing member demographics, such as a bar chart of members by city or region (from the `address` field).                         | 🟡 Amber   |

### 4. System & Security

| Feature                       | Description                                                                                                                                                             | RAG Rating |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- |
| **User Role Management**      | Create a `user_roles` table and an admin interface to manage user roles and permissions, allowing for more granular control than just the `user_id=1` rule.            | 🔴 Red     |
| **Activity Log**              | Create a log that records all major actions taken by admins (e.g., "Admin X deleted member Y," "Admin Z sent a bulk email"). This is crucial for security and auditing. | 🔴 Red     |
| **Password Reset for Admins** | Improve the "Forgot Password" functionality for admin users to be more secure, using email-based tokens instead of simple reminders.                                  | 🟡 Amber   |

### 5. User/Member Features

| Feature                       | Description                                                                                                                                                                | RAG Rating |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- |
| **Member Profile Page**       | Create a simple "My Profile" page where logged-in members can view their own information and subscription status.                                                          | 🟡 Amber   |
| **Member-Facing Event List**  | Create a page for registered members to see a list of all current and upcoming events that may not be visible to the general public.                                       | 🟢 Green    |

--- 