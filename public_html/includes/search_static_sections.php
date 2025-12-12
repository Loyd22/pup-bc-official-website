<?php
/**
 * Static Section Titles Index for Global Search
 * 
 * This file contains an array of static section titles (card headings, section headers)
 * that should be searchable across the site. These are typically h2-h3 headings or
 * card titles that act like mini pages within a larger page.
 * 
 * To add more sections:
 * 1. Add a unique ID to the section container in the HTML (e.g., id="section-name")
 * 2. Add an entry to the $STATIC_SECTIONS array below with:
 *    - title: The section heading text
 *    - url: Path to page with anchor (e.g., 'contact.php#locate-us')
 *    - snippet: Short description (used in search results)
 *    - type: Always 'Page section' for static sections
 */

$STATIC_SECTIONS = [
    // Contact Page Sections
    [
        'title' => 'Locate Us',
        'url' => 'contact.php#locate-us',
        'snippet' => 'Map and directions to PUP Biñan campus location.',
        'type' => 'Page section'
    ],
    [
        'title' => 'Meet Us',
        'url' => 'contact.php#meet-us',
        'snippet' => 'Contact information including address, phone, and email for PUP Biñan Campus.',
        'type' => 'Page section'
    ],
    [
        'title' => 'Social',
        'url' => 'contact.php#social',
        'snippet' => 'Connect with PUP Biñan Campus on social media platforms.',
        'type' => 'Page section'
    ],
    
    // Student Services Highlight Cards
    [
        'title' => 'Enter & Stay in PUP',
        'url' => 'services.php#enter-stay-pup',
        'snippet' => 'Admissions, PUPCET, enrollment, subject loading, COR, ID validation, and student records (Form 137/138, certifications, TOR).',
        'type' => 'Page section'
    ],
    [
        'title' => 'Well-being & Academic Support',
        'url' => 'services.php#wellbeing-support',
        'snippet' => 'Guidance and counseling, career planning, wellness activities, and library services that support study, research, and information access.',
        'type' => 'Page section'
    ],
    [
        'title' => 'Aid, Life & Leadership',
        'url' => 'services.php#aid-life-leadership',
        'snippet' => 'Scholarships and grants, TES and financial assistance, plus student organizations, councils, and campus life programs that build leadership.',
        'type' => 'Page section'
    ],
    
    // Student Services FAQ Section (already has id="faqs")
    [
        'title' => 'Frequently asked questions',
        'url' => 'services.php#faqs',
        'snippet' => 'Quick answers to common questions about student services, offices, and campus resources.',
        'type' => 'Page section'
    ],
    
    // Note: Other sections like office cards already have IDs and are handled in the main search index
    // This file focuses on card-level sections that act as distinct content areas
];

