<?php
include __DIR__ . '/DATAANALYTICS/db.php';
require_once __DIR__ . '/DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/includes/logo_helper.php';

$page_name = 'privacy-policy.php';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);
$page_views = get_page_visit_count($conn, $page_name);

$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int) ($row['total'] ?? 0);
  $result->free();
}

$page_title = 'Privacy Policy - PUP Biñan Campus';
$page_description = 'Learn how the PUP Biñan Campus website collects, uses, and protects information in line with the Data Privacy Act of 2012.';
$body_class = 'page-privacy';
$activePage = 'privacy';
$basePath = '';
$siteTitle = 'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES';
$campusName = 'Bi&ntilde;an Campus';

include __DIR__ . '/includes/header.php';
?>

<main class="page-privacy">
  <section class="section">
    <div class="container">
      <article class="card">
        <div class="body">
          <h1>PUP Biñan Campus Website Privacy Policy</h1>

          <p><strong>Effective date:</strong> (December 15, 2025)</p>

          <p>
            PUP Biñan Campus, as part of the Polytechnic University of the Philippines (PUP),
            respects your right to privacy and complies with the Data Privacy Act of 2012
            (Republic Act No. 10173) and its Implementing Rules and Regulations.
          </p>
          <p>
            This Privacy Policy explains how we collect, use, store, and protect personal data when you
            visit the PUP Biñan Campus website (<strong>pupbc.site</strong>).
          </p>

          <section class="policy-section">
            <h2>1. Scope</h2>
            <p>This Policy applies to all pages under <strong>pupbc.site</strong>, including:</p>
            <ul>
              <li>Homepage and information pages</li>
              <li>Academic Programs, Admission, Student Services, Campus Life, News or Announcements, Gallery</li>
              <li>Contact or inquiry forms</li>
              <li>The admin area used by authorized campus personnel</li>
            </ul>
            <p>
              It does not cover other websites that we link to, such as
              <a href="https://www.pup.edu.ph" target="_blank" rel="noopener noreferrer">www.pup.edu.ph</a>
              or social media pages.
            </p>
          </section>

          <section class="policy-section">
            <h2>2. Personal data we collect</h2>

            <h3>2.1 Information you provide</h3>
            <p>We may collect personal data when you:</p>
            <ul>
              <li>Submit inquiries through the Contact Us or other online forms</li>
              <li>Request information about academic programs, admissions, events, or services</li>
              <li>Log in as an authorized admin to manage content</li>
            </ul>
            <p>Typical data may include:</p>
            <ul>
              <li>Name</li>
              <li>Email address</li>
              <li>Contact number (if requested)</li>
              <li>Program, role, or campus affiliation (if requested)</li>
              <li>Your message or inquiry</li>
              <li>For admins: username and activity logs</li>
            </ul>

            <h3>2.2 Information collected automatically</h3>
            <p>
              When you visit <strong>pupbc.site</strong>, our systems and analytics modules may automatically collect:
            </p>
            <ul>
              <li>IP address</li>
              <li>Browser type and version</li>
              <li>Device type and operating system</li>
              <li>Date and time of visit</li>
              <li>Pages visited and referring page or link</li>
              <li>Basic cookie or session information (for example to keep you logged in or improve navigation)</li>
            </ul>
            <p>
              These data are mainly used in page visit and page view analytics to understand how visitors use the
              website and to improve content.
            </p>
          </section>

          <section class="policy-section">
            <h2>3. Purposes of processing</h2>
            <p>We collect and process personal data only for legitimate purposes, including:</p>
            <ul>
              <li>Responding to your inquiries or requests</li>
              <li>Providing information about PUP Biñan’s academic programs, services, and activities</li>
              <li>Operating, maintaining, and securing the website and admin panel</li>
              <li>Generating aggregated statistics on website traffic and usage</li>
              <li>Preparing reports and documentation required by the University or government agencies</li>
              <li>Complying with legal and regulatory requirements</li>
            </ul>
            <p>We do not sell or rent your personal data.</p>
          </section>

          <section class="policy-section">
            <h2>4. Legal basis</h2>
            <p>We process personal data on the basis of:</p>
            <ul>
              <li>Performance of PUP’s mandate as a state university and provider of public education</li>
              <li>Compliance with legal and regulatory obligations</li>
              <li>
                Your consent, when you voluntarily submit information through online forms or agree to optional
                communications
              </li>
            </ul>
            <p>
              You may withdraw your consent at any time, subject to applicable laws and university policies.
            </p>
          </section>

          <section class="policy-section">
            <h2>5. Data sharing and disclosure</h2>
            <p>Personal data collected through this website may be shared with:</p>
            <ul>
              <li>
                Relevant PUP Biñan Campus offices or units that need the information to respond to your request
              </li>
              <li>Other PUP offices when coordination is required</li>
              <li>
                Service providers that host or support the website, email, or analytics, under appropriate data
                protection agreements
              </li>
              <li>
                Government agencies, regulators, or law enforcement when required by law or valid order
              </li>
            </ul>
            <p>We do not share your personal data with third parties for commercial marketing.</p>
          </section>

          <section class="policy-section">
            <h2>6. Data storage, security, and retention</h2>
            <p>
              Data are stored in systems and databases controlled by PUP Biñan or its authorized service providers.
            </p>
            <p>
              We implement reasonable and appropriate organizational, physical, and technical measures to protect
              personal data against loss, misuse, unauthorized access, alteration, or disclosure.
            </p>
            <p>Access to admin features and analytics is restricted to authorized personnel.</p>
            <p>Personal data are kept:</p>
            <ul>
              <li>Only as long as necessary to fulfill the purposes stated in this Policy</li>
              <li>Or as required by PUP records management rules and applicable laws</li>
            </ul>
            <p>
              After that, data may be securely deleted, anonymized, or archived.
            </p>
          </section>

          <section class="policy-section">
            <h2>7. Cookies and analytics</h2>
            <p>The website may use cookies or similar technologies to:</p>
            <ul>
              <li>Maintain sessions and improve navigation</li>
              <li>Measure page visits, clicks, and other anonymous usage statistics</li>
            </ul>
            <p>
              Any analytics reports generated from these data are aggregated and do not directly identify individual
              visitors.
            </p>
            <p>
              You can adjust your browser settings to block or delete cookies, but this may affect some website
              functions.
            </p>
          </section>

          <section class="policy-section">
            <h2>8. Your data privacy rights</h2>
            <p>
              Under the Data Privacy Act of 2012, you have the following rights, subject to limitations under the law:
            </p>
            <ul>
              <li>Right to be informed</li>
              <li>Right to access</li>
              <li>Right to object</li>
              <li>Right to erasure or blocking</li>
              <li>Right to rectify or correct data</li>
              <li>Right to data portability</li>
              <li>Right to file a complaint with the National Privacy Commission</li>
            </ul>
            <p>
              You may exercise these rights by contacting the University using the details below.
            </p>
          </section>

          <section class="policy-section">
            <h2>9. Third-party websites and links</h2>
            <p>
              Our website may contain links to other PUP websites, government portals, or external services. Their
              privacy practices may differ from ours. This Policy applies only to <strong>pupbc.site</strong> and not to
              external websites.
            </p>
          </section>

          <section class="policy-section">
            <h2>10. Contact information</h2>
            <p>
              For questions or concerns about this Privacy Policy or your data privacy rights related to this website,
              you may contact:
            </p>
            <p><strong>PUP Biñan Campus Administration</strong><br>
              (insert campus office email or phone if available)
            </p>
            <p>or the <strong>Polytechnic University of the Philippines</strong> through:</p>
            <ul>
              <li>Phone: (+63 2) 5335-1PUP (5335-1787) or 5335-1777</li>
              <li>Email: <a href="mailto:inquire@pup.edu.ph">inquire@pup.edu.ph</a></li>
            </ul>
          </section>

          <section class="policy-section">
            <h2>11. Relation to the PUP Privacy Statement</h2>
            <p>
              This website follows and supplements the official Polytechnic University of the Philippines Privacy
              Statement, which describes how the University generally collects and uses personal data in its systems and
              services. You may view it at:
            </p>
            <p>
              <a href="https://www.pup.edu.ph/privacy" target="_blank" rel="noopener noreferrer">
                https://www.pup.edu.ph/privacy
              </a>
            </p>
            <p>In case of inconsistency, the PUP Privacy Statement and applicable University policies prevail.</p>
          </section>

          <section class="policy-section">
            <h2>12. Changes to this Policy</h2>
            <p>
              We may update this Privacy Policy from time to time to reflect changes in our practices, legal
              requirements, or website features. Any significant changes will be posted on this page with an updated
              effective date.
            </p>
          </section>
        </div>
      </article>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
