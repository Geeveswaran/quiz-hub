<?php
// College list configuration
define('COLLEGES', [
    'kongu-polytechnic' => 'Kongu Polytechnic',
    'banari-amman' => 'Banari Amman Institute of Technology',
    'kongu-engineering' => 'Kongu Engineering College',
    'ksr-college' => 'KSR College of Engineering',
    'kumaraguru' => 'Kumaraguru College of Technology',
    'anna-university' => 'Anna University',
    'psg-college' => 'PSG College of Technology',
    'sairam-engineering' => 'Sairam Engineering College',
    'satyabhama' => 'Satyabhama Institute of Science and Technology',
    'vel-tech' => 'Vel Tech Rangarajan Dr. Sagunthala R&D Institute'
]);

// Function to get college list as array
function getCollegeList() {
    return COLLEGES;
}

// Function to get college name by ID
function getCollegeName($collegeId) {
    return COLLEGES[$collegeId] ?? 'Unknown College';
}
