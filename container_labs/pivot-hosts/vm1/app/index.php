<?php
require_once 'config.php';
if (is_logged_in()) redirect('/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocuShare - Secure Document Management</title>
    <meta name="description" content="DocuShare - Enterprise Document Management and File Sharing Platform">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="landing-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-layer-group"></i>
                <span>DocuShare</span>
            </div>
            <div class="nav-links">
                <a href="/login.php" class="btn btn-outline">Sign In</a>
                <a href="/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-shield-alt"></i>
                Enterprise Security · ISO 27001 Certified
            </div>
            <h1>Secure Document Management for Modern Teams</h1>
            <p class="hero-subtitle">Store, share, and collaborate on documents with military-grade security. DocuShare keeps your sensitive files protected and accessible.</p>
            <div class="hero-actions">
                <a href="/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Start Free Trial
                </a>
                <a href="/login.php" class="btn btn-ghost btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">50K+</span>
                    <span class="stat-label">Organizations</span>
                </div>
                <div class="stat">
                    <span class="stat-number">99.9%</span>
                    <span class="stat-label">Uptime</span>
                </div>
                <div class="stat">
                    <span class="stat-number">256-bit</span>
                    <span class="stat-label">Encryption</span>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="file-preview-card glass">
                <div class="file-header">
                    <div class="file-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="file-title">documents</span>
                </div>
                <div class="file-list">
                    <div class="file-item">
                        <i class="fas fa-file-pdf" style="color:#ef4444"></i>
                        <span>Q4_Report_2024.pdf</span>
                        <small>2.4 MB</small>
                    </div>
                    <div class="file-item">
                        <i class="fas fa-file-image" style="color:#3b82f6"></i>
                        <span>company_logo.svg</span>
                        <small>45 KB</small>
                    </div>
                    <div class="file-item active">
                        <i class="fas fa-file-code" style="color:#10b981"></i>
                        <span>api_schema.xml</span>
                        <small>128 KB</small>
                    </div>
                    <div class="file-item">
                        <i class="fas fa-file-word" style="color:#6366f1"></i>
                        <span>Project_Proposal.docx</span>
                        <small>892 KB</small>
                    </div>
                    <div class="file-item">
                        <i class="fas fa-file-image" style="color:#f59e0b"></i>
                        <span>banner_design.svg</span>
                        <small>67 KB</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Everything Your Team Needs</h2>
            <div class="features-grid">
                <div class="feature-card glass">
                    <div class="feature-icon" style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Smart Upload</h3>
                    <p>Upload any file type — PDFs, images, SVGs, XML files, and more. Our intelligent parser handles everything.</p>
                </div>
                <div class="feature-card glass">
                    <div class="feature-icon" style="background: linear-gradient(135deg,#0ea5e9,#06b6d4)">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>File Preview</h3>
                    <p>Instantly preview documents in the browser. No need to download files to view their contents.</p>
                </div>
                <div class="feature-card glass">
                    <div class="feature-icon" style="background: linear-gradient(135deg,#10b981,#059669)">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Access Control</h3>
                    <p>Fine-grained permissions per user and team. Share only what needs to be shared.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-brand">
                <i class="fas fa-layer-group"></i>
                <span>DocuShare v2.4.1</span>
            </div>
            <p>&copy; 2024 DocuShare Inc. All rights reserved. | <a href="#">Privacy</a> | <a href="#">Terms</a></p>
        </div>
    </footer>
</body>
</html>
