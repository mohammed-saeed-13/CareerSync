<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirectToDashboard(currentUser()['role']);
}

$pageTitle = 'Welcome';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-content fade-in">
    <div class="hero-badge">🚀 AI-Powered Campus Placement Platform</div>
    <h1>
      Land Your Dream Job With<br>
      <span class="highlight">Smart Placement Intelligence</span>
    </h1>
    <p>
      CareerSync connects students, recruiters, and alumni in one unified ecosystem.
      AI-driven resume analysis, real-time drive notifications, skill gap prediction,
      and smart career guidance — all in one place.
    </p>
    <div class="hero-actions">
      <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        Get Started Free
      </a>
      <a href="<?= APP_URL ?>/login.php" class="btn btn-secondary btn-lg">Login to Dashboard</a>
    </div>
  </div>
</section>

<section style="background: var(--bg-secondary); padding: 2rem 0;">
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <h3>Smart Eligibility Matching</h3>
      <p>Automatically match students to drives based on CGPA, branch, skills, and backlog criteria.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background: #ede9fe; color: #7c3aed;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 0 2h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1 0-2h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/></svg>
      </div>
      <h3>AI Resume Analyzer</h3>
      <p>Gemini-powered resume scoring, ATS compatibility check, and personalized improvement suggestions.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background: #fef3c7; color: #d97706;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <h3>Real Analytics</h3>
      <p>Branch-wise placement stats, salary trends, skill demand heatmaps, and year-wise growth charts.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background: #dcfce7; color: #16a34a;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <h3>Alumni Connect</h3>
      <p>Book mentorship sessions, receive job referrals from seniors at top companies.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background: #fee2e2; color: #dc2626;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <h3>AI Career Chatbot</h3>
      <p>Context-aware AI that checks your eligibility, answers drive queries, and gives interview tips in real-time.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background: #dbeafe; color: #2563eb;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <h3>Skill Gap Prediction</h3>
      <p>ML-based analysis shows exactly which skills placed students had that you're missing.</p>
    </div>
  </div>
</section>

<footer style="background: var(--nav-bg); color: var(--nav-text); text-align:center; padding: 1.5rem; font-size: 0.85rem; opacity: 0.8;">
  © <?= date('Y') ?> CareerSync – Smart Campus Placement Ecosystem
</footer>

<?php include __DIR__ . '/includes/footer.php'; ?>
