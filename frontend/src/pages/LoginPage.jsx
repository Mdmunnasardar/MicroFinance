import { useEffect, useState } from 'react';
import { Navigate, useLocation, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { motion, AnimatePresence } from 'framer-motion';

export default function LoginPage() {
  // ===== ORIGINAL LOGIC – UNCHANGED =====
  const { login, status } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
  const [activeTestimonial, setActiveTestimonial] = useState(0);

  useEffect(() => {
    document.title = 'MicroFinance - Empowering Communities Through Smarter Finance';
    if (location.state?.openLogin) {
      setIsLoginModalOpen(true);
    }
    const interval = setInterval(() => {
      setActiveTestimonial((prev) => (prev + 1) % testimonials.length);
    }, 5000);
    return () => clearInterval(interval);
  }, [location]);

  if (status === 'authenticated') {
    const target = location.state?.from || '/';
    return <Navigate to={target} replace />;
  }

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setSubmitting(true);
    try {
      await login(username, password);
      const target = location.state?.from || '/';
      navigate(target, { replace: true });
    } catch (err) {
      setError(err.message || 'Unable to sign in. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  // ===== DATA =====
  const stats = [
    { value: '10,000+', label: 'Active Members', icon: 'fa-users', color: '#4f46e5' },
    { value: '$5.2M', label: 'Loans Disbursed', icon: 'fa-hand-holding-usd', color: '#22c55e' },
    { value: '$3.8M', label: 'Total Savings', icon: 'fa-piggy-bank', color: '#f59e0b' },
    { value: '98.7%', label: 'Repayment Rate', icon: 'fa-percent', color: '#06b6d4' },
  ];

  const features = [
    { icon: 'fa-users', title: 'Member Management', desc: 'Complete member profiles with history, documents, and activity tracking.', color: '#4f46e5' },
    { icon: 'fa-hand-holding-usd', title: 'Loan Management', desc: 'End-to-end loan processing with automated approvals and repayment scheduling.', color: '#22c55e' },
    { icon: 'fa-piggy-bank', title: 'Savings Management', desc: 'Track member savings with interest calculations and balance statements.', color: '#f59e0b' },
    { icon: 'fa-calendar-check', title: 'Installment Tracking', desc: 'Automated scheduling with SMS reminders and late payment alerts.', color: '#06b6d4' },
    { icon: 'fa-layer-group', title: 'Committee Management', desc: 'Organize members into committees with group loan capabilities.', color: '#8b5cf6' },
    { icon: 'fa-chart-pie', title: 'Analytics & Reports', desc: 'Real-time dashboards with custom reports and predictive analytics.', color: '#ec4899' },
  ];

  const testimonials = [
    { 
      name: 'Sarah Johnson', 
      role: 'NGO Director', 
      org: 'Empower Africa', 
      quote: 'MicroFinance has transformed how we manage our community programs. The efficiency gains are remarkable.',
      image: 'https://ui-avatars.com/api/?name=Sarah+Johnson&background=4f46e5&color=fff&size=60',
      rating: 5 
    },
    { 
      name: 'Michael Chen', 
      role: 'Finance Manager', 
      org: 'Asia Micro Credit', 
      quote: 'The reporting capabilities are outstanding. We now make data-driven decisions in real-time.',
      image: 'https://ui-avatars.com/api/?name=Michael+Chen&background=7c3aed&color=fff&size=60',
      rating: 5 
    },
    { 
      name: 'Amara Okafor', 
      role: 'Field Officer', 
      org: 'Nigeria Micro Finance', 
      quote: 'The mobile app makes field work so much easier. I can manage everything from my phone.',
      image: 'https://ui-avatars.com/api/?name=Amara+Okafor&background=22c55e&color=fff&size=60',
      rating: 5 
    },
  ];

  const benefits = [
    { icon: 'fa-clock', title: '70% Time Savings', desc: 'Automate routine tasks and reduce manual processing time significantly.' },
    { icon: 'fa-chart-line', title: 'Data-Driven Decisions', desc: 'Make informed decisions with real-time analytics and predictive insights.' },
    { icon: 'fa-shield-alt', title: 'Enterprise Security', desc: 'Bank-grade encryption with granular access controls and audit trails.' },
    { icon: 'fa-mobile-alt', title: 'Anywhere Access', desc: 'Manage operations from any device with responsive web and mobile apps.' },
  ];

  // ============================================================
  // UNIQUE PREMIUM UI
  // ============================================================
  return (
    <div style={styles.page}>
      {/* ===== NAVBAR ===== */}
      <nav style={styles.navbar}>
        <div style={styles.navContainer}>
          <Link to="/" style={styles.navLogo}>
            <div style={styles.navLogoIcon}>
              <i className="fa-solid fa-hand-holding-heart" />
            </div>
            <div>
              <span style={styles.navLogoText}>MicroFinance</span>
              <span style={styles.navLogoSub}>Management System</span>
            </div>
          </Link>

          <div style={styles.navLinks}>
            <a href="#features" style={styles.navLink}>Features</a>
            <a href="#benefits" style={styles.navLink}>Benefits</a>
            <a href="#how-it-works" style={styles.navLink}>How It Works</a>
            <a href="#testimonials" style={styles.navLink}>Testimonials</a>
            <button
              onClick={() => setIsLoginModalOpen(true)}
              style={styles.navLoginBtn}
            >
              <i className="fa-solid fa-sign-in-alt" style={{ marginRight: 8 }} />
              Login
            </button>
          </div>

          <button style={styles.mobileBtn} onClick={() => {}}>
            <i className="fa-solid fa-bars" />
          </button>
        </div>
      </nav>

      {/* ===== HERO ===== */}
      <section style={styles.heroSection}>
        <div style={styles.heroGlow1} />
        <div style={styles.heroGlow2} />
        <div style={styles.heroGlow3} />
        <div style={styles.heroGrid} />

        <div style={styles.heroContainer}>
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7 }}
            style={styles.heroContent}
          >
            <div style={styles.heroBadge}>
              <span style={styles.heroBadgeDot} />
              <span style={styles.heroBadgeText}>Trusted by 10,000+ Members</span>
            </div>

            <h1 style={styles.heroTitle}>
              Empowering Communities
              <br />
              Through{' '}
              <span style={styles.heroTitleGradient}>Smarter Finance</span>
            </h1>

            <p style={styles.heroDescription}>
              The all-in-one microfinance platform that helps NGOs and financial institutions 
              streamline operations, reduce costs, and drive financial inclusion.
            </p>

            <div style={styles.heroButtons}>
              <button
                onClick={() => setIsLoginModalOpen(true)}
                style={styles.heroPrimaryBtn}
              >
                <i className="fa-solid fa-rocket" style={{ marginRight: 10 }} />
                Get Started
              </button>
              <a href="#features" style={styles.heroSecondaryBtn}>
                <i className="fa-solid fa-arrow-right" style={{ marginRight: 8 }} />
                Explore Features
              </a>
            </div>

            <div style={styles.trustBadges}>
              <div style={styles.trustBadge}>
                <i className="fa-solid fa-shield-alt" style={styles.trustIcon} />
                <span style={styles.trustText}>Bank-Grade Security</span>
              </div>
              <div style={styles.trustDivider} />
              <div style={styles.trustBadge}>
                <i className="fa-solid fa-users" style={styles.trustIcon} />
                <span style={styles.trustText}>10K+ Users</span>
              </div>
              <div style={styles.trustDivider} />
              <div style={styles.trustBadge}>
                <i className="fa-solid fa-star" style={styles.trustIcon} />
                <span style={styles.trustText}>4.9/5 Rating</span>
              </div>
            </div>
          </motion.div>

          {/* Hero Illustration - Unique Design */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.7, delay: 0.2 }}
            style={styles.heroIllustration}
          >
            <div style={styles.illustrationCard}>
              <div style={styles.illustrationHeader}>
                <div style={styles.illustrationDots}>
                  <span style={{ ...styles.illustrationDot, background: '#ef4444' }} />
                  <span style={{ ...styles.illustrationDot, background: '#f59e0b' }} />
                  <span style={{ ...styles.illustrationDot, background: '#22c55e' }} />
                </div>
                <span style={styles.illustrationTitle}>Dashboard Overview</span>
              </div>
              <div style={styles.illustrationBody}>
                <div style={styles.illustrationStats}>
                  {stats.map((stat, i) => (
                    <div key={i} style={styles.illustrationStat}>
                      <div style={{ ...styles.illustrationStatIcon, background: `${stat.color}20`, color: stat.color }}>
                        <i className={`fa-solid ${stat.icon}`} />
                      </div>
                      <div>
                        <div style={styles.illustrationStatValue}>{stat.value}</div>
                        <div style={styles.illustrationStatLabel}>{stat.label}</div>
                      </div>
                    </div>
                  ))}
                </div>
                <div style={styles.illustrationChart}>
                  <div style={styles.chartBar1} />
                  <div style={styles.chartBar2} />
                  <div style={styles.chartBar3} />
                  <div style={styles.chartBar4} />
                  <div style={styles.chartBar5} />
                  <div style={styles.chartBar6} />
                </div>
              </div>
            </div>

            <motion.div
              animate={{ y: [-8, 8, -8] }}
              transition={{ duration: 4, repeat: Infinity }}
              style={styles.floatingBadge1}
            >
              <i className="fa-solid fa-arrow-trend-up" />
              +32% Growth
            </motion.div>
            <motion.div
              animate={{ y: [8, -8, 8] }}
              transition={{ duration: 4, delay: 1, repeat: Infinity }}
              style={styles.floatingBadge2}
            >
              <i className="fa-solid fa-check-circle" />
              98.7% Repayment
            </motion.div>
          </motion.div>
        </div>
      </section>

      {/* ===== STATS ===== */}
      <section style={styles.statsSection}>
        <div style={styles.sectionContainer}>
          <div style={styles.statsGrid}>
            {stats.map((stat, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.1 }}
                style={styles.statItem}
              >
                <div style={{ ...styles.statIcon, background: `${stat.color}20`, color: stat.color }}>
                  <i className={`fa-solid ${stat.icon}`} />
                </div>
                <div style={styles.statValue}>{stat.value}</div>
                <div style={styles.statLabel}>{stat.label}</div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* ===== FEATURES ===== */}
      <section id="features" style={styles.featuresSection}>
        <div style={styles.sectionContainer}>
          <div style={styles.sectionHeader}>
            <span style={styles.sectionTag}>Features</span>
            <h2 style={styles.sectionTitle}>Everything You Need to Succeed</h2>
            <p style={styles.sectionSubtitle}>
              A complete suite of tools designed specifically for microfinance operations
            </p>
          </div>

          <div style={styles.featuresGrid}>
            {features.map((feature, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.08 }}
                style={styles.featureCard}
              >
                <div style={{ ...styles.featureIcon, background: `${feature.color}20`, color: feature.color }}>
                  <i className={`fa-solid ${feature.icon}`} />
                </div>
                <h3 style={styles.featureTitle}>{feature.title}</h3>
                <p style={styles.featureDesc}>{feature.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* ===== BENEFITS ===== */}
      <section id="benefits" style={styles.benefitsSection}>
        <div style={styles.sectionContainer}>
          <div style={styles.sectionHeader}>
            <span style={styles.sectionTag}>Benefits</span>
            <h2 style={styles.sectionTitle}>Why Choose MicroFinance</h2>
          </div>

          <div style={styles.benefitsGrid}>
            {benefits.map((benefit, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.1 }}
                style={styles.benefitCard}
              >
                <div style={styles.benefitIcon}>
                  <i className={`fa-solid ${benefit.icon}`} />
                </div>
                <div>
                  <h3 style={styles.benefitTitle}>{benefit.title}</h3>
                  <p style={styles.benefitDesc}>{benefit.desc}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* ===== HOW IT WORKS ===== */}
      <section id="how-it-works" style={styles.howSection}>
        <div style={styles.sectionContainer}>
          <div style={styles.sectionHeader}>
            <span style={styles.sectionTag}>How It Works</span>
            <h2 style={styles.sectionTitle}>Get Started in 4 Simple Steps</h2>
          </div>

          <div style={styles.stepsGrid}>
            {[
              { number: '01', icon: 'fa-user-plus', title: 'Sign Up & Setup', desc: 'Create your organization account and configure your settings.' },
              { number: '02', icon: 'fa-users', title: 'Add Members', desc: 'Register members with their personal information and assign to committees.' },
              { number: '03', icon: 'fa-hand-holding-usd', title: 'Manage Loans & Savings', desc: 'Process loan applications, track savings, and manage installments.' },
              { number: '04', icon: 'fa-chart-line', title: 'Track & Report', desc: 'Monitor performance with real-time dashboards and comprehensive reports.' },
            ].map((step, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.1 }}
                style={styles.stepCard}
              >
                <div style={styles.stepNumber}>{step.number}</div>
                <div style={styles.stepIcon}>
                  <i className={`fa-solid ${step.icon}`} />
                </div>
                <h3 style={styles.stepTitle}>{step.title}</h3>
                <p style={styles.stepDesc}>{step.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* ===== TESTIMONIALS - FIXED OVERLAP ===== */}
      <section id="testimonials" style={styles.testimonialsSection}>
        <div style={styles.sectionContainer}>
          <div style={styles.sectionHeader}>
            <span style={styles.sectionTag}>Testimonials</span>
            <h2 style={styles.sectionTitle}>What Our Users Say</h2>
            <p style={styles.sectionSubtitle}>Real stories from real people</p>
          </div>

          <div style={styles.testimonialsContainer}>
            <div style={styles.testimonialsWrapper}>
              {testimonials.map((t, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, x: 50 }}
                  animate={{ 
                    opacity: activeTestimonial === i ? 1 : 0,
                    x: activeTestimonial === i ? 0 : 50,
                    scale: activeTestimonial === i ? 1 : 0.95,
                    display: activeTestimonial === i ? 'flex' : 'none',
                  }}
                  transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
                  style={styles.testimonialCard}
                >
                  <div style={styles.testimonialStars}>
                    {[...Array(t.rating)].map((_, j) => (
                      <i key={j} className="fa-solid fa-star" style={{ color: '#f59e0b', marginRight: 2 }} />
                    ))}
                  </div>
                  <p style={styles.testimonialQuote}>"{t.quote}"</p>
                  <div style={styles.testimonialAuthor}>
                    <img src={t.image} alt={t.name} style={styles.testimonialImage} />
                    <div>
                      <div style={styles.testimonialName}>{t.name}</div>
                      <div style={styles.testimonialRole}>{t.role} · {t.org}</div>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>

            <div style={styles.testimonialDots}>
              {testimonials.map((_, i) => (
                <button
                  key={i}
                  onClick={() => setActiveTestimonial(i)}
                  style={{
                    ...styles.testimonialDot,
                    background: activeTestimonial === i ? '#4f46e5' : 'rgba(255,255,255,0.1)',
                    width: activeTestimonial === i ? '40px' : '10px',
                  }}
                />
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* ===== CTA ===== */}
      <section style={styles.ctaSection}>
        <div style={styles.ctaContainer}>
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            style={styles.ctaContent}
          >
            <div style={styles.ctaBadge}>
              <i className="fa-solid fa-rocket" />
              Get Started Today
            </div>
            <h2 style={styles.ctaTitle}>
              Ready to Transform Your{' '}
              <span style={styles.heroTitleGradient}>Microfinance</span> Operations?
            </h2>
            <p style={styles.ctaDescription}>
              Join 10,000+ organizations already using MicroFinance to manage their communities effectively.
            </p>
            <button
              onClick={() => setIsLoginModalOpen(true)}
              style={styles.ctaButton}
            >
              <i className="fa-solid fa-rocket" style={{ marginRight: 10 }} />
              Login to Dashboard
            </button>
          </motion.div>
        </div>
      </section>

      {/* ===== FOOTER ===== */}
      <footer style={styles.footer}>
        <div style={styles.footerContainer}>
          <div style={styles.footerBrand}>
            <div style={styles.footerLogo}>
              <i className="fa-solid fa-hand-holding-heart" />
              <span>MicroFinance</span>
            </div>
            <p style={styles.footerText}>
              Empowering communities through smarter finance.
            </p>
          </div>
          <div style={styles.footerLinks}>
            <a href="#features" style={styles.footerLink}>Features</a>
            <a href="#benefits" style={styles.footerLink}>Benefits</a>
            <a href="#how-it-works" style={styles.footerLink}>How It Works</a>
            <a href="#testimonials" style={styles.footerLink}>Testimonials</a>
            <button
              onClick={() => setIsLoginModalOpen(true)}
              style={styles.footerLoginBtn}
            >
              Login
            </button>
          </div>
        </div>
        <div style={styles.footerBottom}>
          <p style={styles.footerCopy}>
            &copy; {new Date().getFullYear()} MicroFinance Management System. All rights reserved.
          </p>
        </div>
      </footer>

      {/* ===== LOGIN MODAL ===== */}
      <AnimatePresence>
        {isLoginModalOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            style={styles.modalOverlay}
            onClick={() => setIsLoginModalOpen(false)}
          >
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
              style={styles.modalContent}
              onClick={(e) => e.stopPropagation()}
            >
              <div style={styles.modalHeader}>
                <div style={styles.modalLogo}>
                  <i className="fa-solid fa-hand-holding-heart" />
                  <span>MicroFinance</span>
                </div>
                <button
                  onClick={() => setIsLoginModalOpen(false)}
                  style={styles.modalClose}
                >
                  <i className="fa-solid fa-times" />
                </button>
              </div>

              <div style={styles.modalBody}>
                <h2 style={styles.modalTitle}>Welcome Back</h2>
                <p style={styles.modalSubtitle}>Sign in to access your dashboard</p>

                {error && (
                  <div style={styles.errorBox}>
                    <i className="fa-solid fa-circle-exclamation" style={styles.errorIcon} />
                    <span style={styles.errorText}>{error}</span>
                  </div>
                )}

                <form onSubmit={handleSubmit} style={styles.modalForm}>
                  <div style={styles.fieldGroup}>
                    <label htmlFor="modal-username" style={styles.label}>Username</label>
                    <div style={styles.inputWrapper}>
                      <i className="fa-regular fa-user" style={styles.inputIcon} />
                      <input
                        id="modal-username"
                        type="text"
                        autoComplete="username"
                        required
                        value={username}
                        onChange={(e) => setUsername(e.target.value)}
                        style={styles.input}
                        placeholder="Enter your username"
                      />
                    </div>
                  </div>

                  <div style={styles.fieldGroup}>
                    <label htmlFor="modal-password" style={styles.label}>Password</label>
                    <div style={styles.inputWrapper}>
                      <i className="fa-regular fa-lock" style={styles.inputIcon} />
                      <input
                        id="modal-password"
                        type={showPassword ? 'text' : 'password'}
                        autoComplete="current-password"
                        required
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        style={styles.input}
                        placeholder="Enter your password"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        style={styles.toggleBtn}
                      >
                        <i className={`fa-regular ${showPassword ? 'fa-eye-slash' : 'fa-eye'}`} />
                      </button>
                    </div>
                  </div>

                  <div style={styles.optionsRow}>
                    <label style={styles.rememberMe}>
                      <input type="checkbox" style={styles.checkbox} />
                      Remember me
                    </label>
                    <a href="#" style={styles.forgotLink}>Forgot password?</a>
                  </div>

                  <button type="submit" disabled={submitting} style={styles.submitBtn}>
                    {submitting ? (
                      <>
                        <span style={styles.spinner} />
                        Signing in…
                      </>
                    ) : (
                      <>
                        <i className="fa-regular fa-right-to-bracket" style={styles.btnIcon} />
                        Sign In
                      </>
                    )}
                  </button>
                </form>

                <div style={styles.modalFooter}>
                  <p style={styles.modalFooterText}>
                    Don't have an account? <a href="#" style={styles.signupLink}>Contact Admin</a>
                  </p>
                </div>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      <style>{`
        @keyframes float-glow {
          0%, 100% { transform: translate(0, 0) scale(1); }
          33% { transform: translate(30px, -30px) scale(1.1); }
          66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
        @keyframes pulse-dot {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.4; }
        }
        .nav-link-hover:hover { color: white !important; }
        .submit-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
        .input-focus:focus { background: rgba(255,255,255,0.06) !important; border-color: #4f46e5 !important; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important; }
        .forgot-link-hover:hover { color: #818cf8 !important; }
        .signup-link-hover:hover { color: #818cf8 !important; }
        .footer-link-hover:hover { color: rgba(255,255,255,0.8) !important; }
        .toggle-hover:hover { color: rgba(255,255,255,0.4) !important; }
        .remember-hover:hover { color: rgba(255,255,255,0.5) !important; }
        .modal-close-hover:hover { background: rgba(255,255,255,0.1) !important; color: white !important; }
        .feature-card-hover:hover { transform: translateY(-4px) !important; border-color: rgba(79, 70, 229, 0.2) !important; box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important; }
        .step-card-hover:hover { transform: translateY(-4px) !important; border-color: rgba(79, 70, 229, 0.15) !important; }
        .hero-primary-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
        .hero-secondary-btn-hover:hover { background: rgba(255,255,255,0.1) !important; border-color: rgba(255,255,255,0.15) !important; }
        .cta-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
        .benefit-card-hover:hover { background: rgba(255,255,255,0.06) !important; transform: translateX(4px) !important; }
        .stat-item-hover:hover { transform: translateY(-4px) !important; }
        .footer-login-btn-hover:hover { background: rgba(79, 70, 229, 0.2) !important; color: #818cf8 !important; }
      `}</style>
    </div>
  );
}

// ============================================================
// STYLES – Unique Premium Design
// ============================================================
const styles = {
  page: {
    minHeight: '100vh',
    backgroundColor: '#0f172a',
    fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
    overflowX: 'hidden',
    color: 'white',
  },

  // ===== NAVBAR =====
  navbar: {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 50,
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    borderBottom: '1px solid rgba(255,255,255,0.05)',
  },
  navContainer: {
    maxWidth: '1200px',
    margin: '0 auto',
    padding: '14px 24px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  navLogo: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    textDecoration: 'none',
  },
  navLogoIcon: {
    width: '40px',
    height: '40px',
    borderRadius: '12px',
    background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    color: 'white',
    fontSize: '18px',
    boxShadow: '0 4px 16px rgba(79, 70, 229, 0.3)',
  },
  navLogoText: {
    fontSize: '18px',
    fontWeight: 700,
    color: 'white',
    letterSpacing: '-0.5px',
  },
  navLogoSub: {
    display: 'block',
    fontSize: '10px',
    color: 'rgba(255,255,255,0.3)',
    marginTop: '-2px',
    textTransform: 'uppercase',
    letterSpacing: '0.5px',
  },
  navLinks: {
    display: 'flex',
    alignItems: 'center',
    gap: '32px',
    '@media (max-width: 968px)': {
      display: 'none',
    },
  },
  navLink: {
    color: 'rgba(255,255,255,0.5)',
    textDecoration: 'none',
    fontSize: '14px',
    fontWeight: 500,
    transition: 'color 0.3s ease',
    cursor: 'pointer',
  },
  navLoginBtn: {
    padding: '8px 24px',
    borderRadius: '10px',
    background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
    color: 'white',
    border: 'none',
    fontSize: '14px',
    fontWeight: 600,
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    boxShadow: '0 4px 16px rgba(79, 70, 229, 0.3)',
    fontFamily: 'Inter, sans-serif',
  },
  mobileBtn: {
    display: 'none',
    background: 'none',
    border: 'none',
    color: 'white',
    fontSize: '20px',
    cursor: 'pointer',
    padding: '8px',
    borderRadius: '8px',
    '@media (max-width: 968px)': {
      display: 'block',
    },
  },

  // ===== HERO =====
  heroSection: {
    position: 'relative',
    minHeight: '100vh',
    display: 'flex',
    alignItems: 'center',
    paddingTop: '80px',
    overflow: 'hidden',
  },
  heroGlow1: {
    position: 'absolute',
    top: '-200px',
    right: '-200px',
    width: '600px',
    height: '600px',
    background: 'rgba(79, 70, 229, 0.12)',
    borderRadius: '50%',
    filter: 'blur(120px)',
    animation: 'float-glow 20s ease-in-out infinite',
    pointerEvents: 'none',
  },
  heroGlow2: {
    position: 'absolute',
    bottom: '-200px',
    left: '-200px',
    width: '500px',
    height: '500px',
    background: 'rgba(124, 58, 237, 0.1)',
    borderRadius: '50%',
    filter: 'blur(120px)',
    animation: 'float-glow 20s ease-in-out infinite',
    animationDelay: '-5s',
    pointerEvents: 'none',
  },
  heroGlow3: {
    position: 'absolute',
    top: '50%',
    left: '50%',
    transform: 'translate(-50%, -50%)',
    width: '400px',
    height: '400px',
    background: 'rgba(6, 182, 212, 0.04)',
    borderRadius: '50%',
    filter: 'blur(120px)',
    animation: 'float-glow 20s ease-in-out infinite',
    animationDelay: '-10s',
    pointerEvents: 'none',
  },
  heroGrid: {
    position: 'absolute',
    inset: 0,
    backgroundImage: 'linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px)',
    backgroundSize: '60px 60px',
    pointerEvents: 'none',
  },
  heroContainer: {
    position: 'relative',
    zIndex: 10,
    maxWidth: '1200px',
    margin: '0 auto',
    padding: '40px 24px',
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '60px',
    alignItems: 'center',
    '@media (max-width: 968px)': {
      gridTemplateColumns: '1fr',
      gap: '40px',
      textAlign: 'center',
    },
  },
  heroContent: {
    '@media (max-width: 968px)': {
      textAlign: 'center',
    },
  },
  heroBadge: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '8px',
    padding: '6px 16px',
    borderRadius: '20px',
    backgroundColor: 'rgba(79, 70, 229, 0.1)',
    border: '1px solid rgba(79, 70, 229, 0.15)',
    marginBottom: '24px',
  },
  heroBadgeDot: {
    width: '6px',
    height: '6px',
    borderRadius: '50%',
    backgroundColor: '#4f46e5',
    animation: 'pulse-dot 2s infinite',
  },
  heroBadgeText: {
    fontSize: '13px',
    fontWeight: 500,
    color: '#818cf8',
  },
  heroTitle: {
    fontSize: '48px',
    fontWeight: 800,
    color: 'white',
    lineHeight: 1.05,
    letterSpacing: '-0.02em',
    marginBottom: '20px',
    '@media (max-width: 768px)': {
      fontSize: '36px',
    },
  },
  heroTitleGradient: {
    background: 'linear-gradient(135deg, #4f46e5, #a78bfa)',
    WebkitBackgroundClip: 'text',
    WebkitTextFillColor: 'transparent',
    backgroundClip: 'text',
  },
  heroDescription: {
    fontSize: '18px',
    color: 'rgba(255,255,255,0.5)',
    lineHeight: 1.7,
    maxWidth: '480px',
    marginBottom: '32px',
    '@media (max-width: 968px)': {
      maxWidth: '100%',
    },
  },
  heroButtons: {
    display: 'flex',
    gap: '16px',
    flexWrap: 'wrap',
    marginBottom: '40px',
    '@media (max-width: 968px)': {
      justifyContent: 'center',
    },
  },
  heroPrimaryBtn: {
    padding: '14px 32px',
    borderRadius: '12px',
    background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
    color: 'white',
    border: 'none',
    fontSize: '16px',
    fontWeight: 600,
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    boxShadow: '0 4px 24px rgba(79, 70, 229, 0.3)',
    fontFamily: 'Inter, sans-serif',
    display: 'flex',
    alignItems: 'center',
  },
  heroSecondaryBtn: {
    padding: '14px 32px',
    borderRadius: '12px',
    background: 'rgba(255,255,255,0.05)',
    color: 'white',
    border: '1px solid rgba(255,255,255,0.08)',
    fontSize: '16px',
    fontWeight: 500,
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    textDecoration: 'none',
    display: 'flex',
    alignItems: 'center',
    fontFamily: 'Inter, sans-serif',
  },
  trustBadges: {
    display: 'flex',
    alignItems: 'center',
    gap: '20px',
    flexWrap: 'wrap',
    '@media (max-width: 480px)': {
      gap: '12px',
      justifyContent: 'center',
    },
  },
  trustBadge: {
    display: 'flex',
    alignItems: 'center',
    gap: '8px',
  },
  trustIcon: {
    color: '#4f46e5',
    fontSize: '16px',
  },
  trustText: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.4)',
  },
  trustDivider: {
    width: '1px',
    height: '24px',
    backgroundColor: 'rgba(255,255,255,0.06)',
  },

  // ===== HERO ILLUSTRATION =====
  heroIllustration: {
    position: 'relative',
    '@media (max-width: 968px)': {
      maxWidth: '500px',
      margin: '0 auto',
    },
  },
  illustrationCard: {
    backgroundColor: 'rgba(255,255,255,0.04)',
    backdropFilter: 'blur(20px)',
    WebkitBackdropFilter: 'blur(20px)',
    borderRadius: '20px',
    border: '1px solid rgba(255,255,255,0.06)',
    overflow: 'hidden',
    boxShadow: '0 25px 60px rgba(0,0,0,0.3)',
  },
  illustrationHeader: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '16px 20px',
    borderBottom: '1px solid rgba(255,255,255,0.05)',
  },
  illustrationDots: {
    display: 'flex',
    gap: '6px',
  },
  illustrationDot: {
    width: '10px',
    height: '10px',
    borderRadius: '50%',
  },
  illustrationTitle: {
    fontSize: '12px',
    color: 'rgba(255,255,255,0.3)',
    fontWeight: 500,
    marginLeft: '4px',
  },
  illustrationBody: {
    padding: '24px',
  },
  illustrationStats: {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '12px',
    marginBottom: '20px',
  },
  illustrationStat: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '10px 12px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '10px',
    border: '1px solid rgba(255,255,255,0.04)',
  },
  illustrationStatIcon: {
    width: '32px',
    height: '32px',
    borderRadius: '8px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '12px',
  },
  illustrationStatValue: {
    fontSize: '14px',
    fontWeight: 700,
    color: 'white',
  },
  illustrationStatLabel: {
    fontSize: '10px',
    color: 'rgba(255,255,255,0.3)',
  },
  illustrationChart: {
    display: 'flex',
    alignItems: 'flex-end',
    gap: '6px',
    height: '60px',
    paddingTop: '12px',
    borderTop: '1px solid rgba(255,255,255,0.04)',
  },
  chartBar1: { flex: 1, height: '50%', background: '#4f46e5', borderRadius: '3px 3px 0 0' },
  chartBar2: { flex: 1, height: '75%', background: '#7c3aed', borderRadius: '3px 3px 0 0' },
  chartBar3: { flex: 1, height: '40%', background: '#22c55e', borderRadius: '3px 3px 0 0' },
  chartBar4: { flex: 1, height: '65%', background: '#f59e0b', borderRadius: '3px 3px 0 0' },
  chartBar5: { flex: 1, height: '85%', background: '#06b6d4', borderRadius: '3px 3px 0 0' },
  chartBar6: { flex: 1, height: '55%', background: '#8b5cf6', borderRadius: '3px 3px 0 0' },

  floatingBadge1: {
    position: 'absolute',
    top: '-12px',
    right: '-12px',
    padding: '8px 14px',
    backgroundColor: 'rgba(34, 197, 94, 0.15)',
    backdropFilter: 'blur(12px)',
    WebkitBackdropFilter: 'blur(12px)',
    borderRadius: '10px',
    border: '1px solid rgba(34, 197, 94, 0.2)',
    color: '#22c55e',
    fontSize: '11px',
    fontWeight: 600,
    display: 'flex',
    alignItems: 'center',
    gap: '6px',
  },
  floatingBadge2: {
    position: 'absolute',
    bottom: '-8px',
    left: '-8px',
    padding: '8px 14px',
    backgroundColor: 'rgba(79, 70, 229, 0.15)',
    backdropFilter: 'blur(12px)',
    WebkitBackdropFilter: 'blur(12px)',
    borderRadius: '10px',
    border: '1px solid rgba(79, 70, 229, 0.2)',
    color: '#818cf8',
    fontSize: '11px',
    fontWeight: 600,
    display: 'flex',
    alignItems: 'center',
    gap: '6px',
  },

  // ===== STATS =====
  statsSection: {
    padding: '60px 24px',
    backgroundColor: 'rgba(255,255,255,0.02)',
  },
  statsGrid: {
    maxWidth: '1200px',
    margin: '0 auto',
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    gap: '20px',
    '@media (max-width: 768px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 480px)': {
      gridTemplateColumns: '1fr',
    },
  },
  statItem: {
    textAlign: 'center',
    padding: '20px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '12px',
    border: '1px solid rgba(255,255,255,0.04)',
    transition: 'all 0.3s ease',
  },
  statIcon: {
    width: '40px',
    height: '40px',
    borderRadius: '10px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto 10px',
    fontSize: '16px',
  },
  statValue: {
    fontSize: '20px',
    fontWeight: 700,
    color: 'white',
  },
  statLabel: {
    fontSize: '12px',
    color: 'rgba(255,255,255,0.3)',
    marginTop: '4px',
  },

  // ===== FEATURES =====
  featuresSection: {
    padding: '80px 24px',
  },
  sectionContainer: {
    maxWidth: '1200px',
    margin: '0 auto',
  },
  sectionHeader: {
    textAlign: 'center',
    marginBottom: '48px',
  },
  sectionTag: {
    display: 'inline-block',
    padding: '4px 16px',
    borderRadius: '20px',
    backgroundColor: 'rgba(79, 70, 229, 0.1)',
    color: '#818cf8',
    fontSize: '13px',
    fontWeight: 600,
    textTransform: 'uppercase',
    letterSpacing: '0.5px',
    marginBottom: '12px',
  },
  sectionTitle: {
    fontSize: '36px',
    fontWeight: 700,
    color: 'white',
    marginBottom: '12px',
    '@media (max-width: 768px)': {
      fontSize: '28px',
    },
  },
  sectionSubtitle: {
    fontSize: '18px',
    color: 'rgba(255,255,255,0.4)',
    maxWidth: '600px',
    margin: '0 auto',
  },
  featuresGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(3, 1fr)',
    gap: '24px',
    '@media (max-width: 968px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 576px)': {
      gridTemplateColumns: '1fr',
    },
  },
  featureCard: {
    padding: '24px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '14px',
    border: '1px solid rgba(255,255,255,0.04)',
    transition: 'all 0.3s ease',
    cursor: 'default',
  },
  featureIcon: {
    width: '44px',
    height: '44px',
    borderRadius: '10px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '18px',
    marginBottom: '14px',
  },
  featureTitle: {
    fontSize: '16px',
    fontWeight: 600,
    color: 'white',
    marginBottom: '6px',
  },
  featureDesc: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.4)',
    lineHeight: 1.6,
  },

  // ===== BENEFITS =====
  benefitsSection: {
    padding: '80px 24px',
    backgroundColor: 'rgba(255,255,255,0.02)',
  },
  benefitsGrid: {
    maxWidth: '1000px',
    margin: '0 auto',
    display: 'grid',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: '20px',
    '@media (max-width: 768px)': {
      gridTemplateColumns: '1fr',
    },
  },
  benefitCard: {
    display: 'flex',
    gap: '16px',
    padding: '20px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '12px',
    border: '1px solid rgba(255,255,255,0.04)',
    transition: 'all 0.3s ease',
    alignItems: 'flex-start',
  },
  benefitIcon: {
    width: '40px',
    height: '40px',
    borderRadius: '10px',
    background: 'rgba(79, 70, 229, 0.15)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '16px',
    color: '#818cf8',
    flexShrink: 0,
  },
  benefitTitle: {
    fontSize: '16px',
    fontWeight: 600,
    color: 'white',
    marginBottom: '4px',
  },
  benefitDesc: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.4)',
    lineHeight: 1.6,
  },

  // ===== HOW IT WORKS =====
  howSection: {
    padding: '80px 24px',
  },
  stepsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    gap: '24px',
    '@media (max-width: 968px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 576px)': {
      gridTemplateColumns: '1fr',
    },
  },
  stepCard: {
    textAlign: 'center',
    padding: '28px 20px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '14px',
    border: '1px solid rgba(255,255,255,0.04)',
    position: 'relative',
    transition: 'all 0.3s ease',
  },
  stepNumber: {
    position: 'absolute',
    top: '12px',
    right: '16px',
    fontSize: '14px',
    fontWeight: 700,
    color: 'rgba(79, 70, 229, 0.3)',
  },
  stepIcon: {
    width: '52px',
    height: '52px',
    borderRadius: '12px',
    background: 'linear-gradient(135deg, rgba(79,70,229,0.15), rgba(124,58,237,0.15))',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto 14px',
    fontSize: '20px',
    color: '#818cf8',
  },
  stepTitle: {
    fontSize: '17px',
    fontWeight: 600,
    color: 'white',
    marginBottom: '6px',
  },
  stepDesc: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.4)',
    lineHeight: 1.6,
  },

  // ===== TESTIMONIALS - FIXED OVERLAP =====
  testimonialsSection: {
    padding: '80px 24px',
    backgroundColor: 'rgba(255,255,255,0.02)',
  },
  testimonialsContainer: {
    maxWidth: '800px',
    margin: '0 auto',
    position: 'relative',
  },
  testimonialsWrapper: {
    position: 'relative',
    minHeight: '250px',
    '@media (max-width: 768px)': {
      minHeight: '300px',
    },
  },
  testimonialCard: {
    position: 'absolute',
    inset: 0,
    padding: '32px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '16px',
    border: '1px solid rgba(255,255,255,0.04)',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
    transition: 'all 0.5s ease',
    boxShadow: '0 4px 24px rgba(0,0,0,0.1)',
  },
  testimonialStars: {
    marginBottom: '12px',
  },
  testimonialQuote: {
    fontSize: '18px',
    color: 'rgba(255,255,255,0.8)',
    lineHeight: 1.7,
    fontStyle: 'italic',
    marginBottom: '16px',
    '@media (max-width: 768px)': {
      fontSize: '15px',
    },
  },
  testimonialAuthor: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
  },
  testimonialImage: {
    width: '48px',
    height: '48px',
    borderRadius: '50%',
    objectFit: 'cover',
    border: '2px solid rgba(79,70,229,0.2)',
  },
  testimonialName: {
    fontSize: '15px',
    fontWeight: 600,
    color: 'white',
  },
  testimonialRole: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.3)',
  },
  testimonialDots: {
    display: 'flex',
    justifyContent: 'center',
    gap: '10px',
    marginTop: '32px',
  },
  testimonialDot: {
    height: '10px',
    borderRadius: '20px',
    border: 'none',
    cursor: 'pointer',
    transition: 'all 0.4s ease',
  },

  // ===== CTA =====
  ctaSection: {
    padding: '80px 24px',
  },
  ctaContainer: {
    maxWidth: '800px',
    margin: '0 auto',
  },
  ctaContent: {
    padding: '48px 40px',
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: '24px',
    border: '1px solid rgba(255,255,255,0.05)',
    textAlign: 'center',
    '@media (max-width: 768px)': {
      padding: '32px 20px',
    },
  },
  ctaBadge: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '8px',
    padding: '6px 16px',
    borderRadius: '20px',
    backgroundColor: 'rgba(34, 197, 94, 0.1)',
    border: '1px solid rgba(34, 197, 94, 0.15)',
    color: '#22c55e',
    fontSize: '13px',
    fontWeight: 500,
    marginBottom: '16px',
  },
  ctaTitle: {
    fontSize: '34px',
    fontWeight: 700,
    color: 'white',
    marginBottom: '16px',
    '@media (max-width: 768px)': {
      fontSize: '26px',
    },
  },
  ctaDescription: {
    fontSize: '18px',
    color: 'rgba(255,255,255,0.4)',
    maxWidth: '600px',
    margin: '0 auto 32px',
    lineHeight: 1.7,
  },
  ctaButton: {
    padding: '14px 36px',
    borderRadius: '12px',
    background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
    color: 'white',
    border: 'none',
    fontSize: '16px',
    fontWeight: 600,
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    boxShadow: '0 4px 24px rgba(79, 70, 229, 0.3)',
    fontFamily: 'Inter, sans-serif',
    display: 'inline-flex',
    alignItems: 'center',
  },

  // ===== FOOTER =====
  footer: {
    padding: '48px 24px 24px',
    borderTop: '1px solid rgba(255,255,255,0.04)',
    backgroundColor: 'rgba(0,0,0,0.2)',
  },
  footerContainer: {
    maxWidth: '1200px',
    margin: '0 auto',
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: '24px',
    '@media (max-width: 768px)': {
      flexDirection: 'column',
      textAlign: 'center',
    },
  },
  footerBrand: {
    display: 'flex',
    flexDirection: 'column',
    gap: '8px',
  },
  footerLogo: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
    color: 'white',
    fontSize: '18px',
    fontWeight: 700,
  },
  footerText: {
    fontSize: '14px',
    color: 'rgba(255,255,255,0.3)',
  },
  footerLinks: {
    display: 'flex',
    gap: '24px',
    alignItems: 'center',
    flexWrap: 'wrap',
    '@media (max-width: 480px)': {
      gap: '16px',
      justifyContent: 'center',
    },
  },
  footerLink: {
    color: 'rgba(255,255,255,0.3)',
    textDecoration: 'none',
    fontSize: '14px',
    transition: 'color 0.3s ease',
  },
  footerLoginBtn: {
    background: 'none',
    border: 'none',
    color: 'rgba(255,255,255,0.3)',
    fontSize: '14px',
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    fontFamily: 'Inter, sans-serif',
    padding: '4px 0',
  },
  footerBottom: {
    maxWidth: '1200px',
    margin: '24px auto 0',
    paddingTop: '16px',
    borderTop: '1px solid rgba(255,255,255,0.04)',
    textAlign: 'center',
  },
  footerCopy: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.12)',
  },

  // ===== MODAL =====
  modalOverlay: {
    position: 'fixed',
    inset: 0,
    zIndex: 100,
    backgroundColor: 'rgba(0,0,0,0.7)',
    backdropFilter: 'blur(8px)',
    WebkitBackdropFilter: 'blur(8px)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '20px',
  },
  modalContent: {
    backgroundColor: 'rgba(30, 41, 59, 0.95)',
    backdropFilter: 'blur(24px)',
    WebkitBackdropFilter: 'blur(24px)',
    borderRadius: '20px',
    maxWidth: '420px',
    width: '100%',
    border: '1px solid rgba(255,255,255,0.06)',
    boxShadow: '0 25px 60px rgba(0,0,0,0.5)',
    overflow: 'hidden',
  },
  modalHeader: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: '16px 24px',
    borderBottom: '1px solid rgba(255,255,255,0.04)',
  },
  modalLogo: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
    color: 'white',
    fontSize: '16px',
    fontWeight: 600,
  },
  modalClose: {
    background: 'none',
    border: 'none',
    color: 'rgba(255,255,255,0.3)',
    fontSize: '18px',
    cursor: 'pointer',
    padding: '4px 8px',
    borderRadius: '8px',
    transition: 'all 0.3s ease',
  },
  modalBody: {
    padding: '24px',
  },
  modalTitle: {
    fontSize: '22px',
    fontWeight: 700,
    color: 'white',
    textAlign: 'center',
    marginBottom: '4px',
  },
  modalSubtitle: {
    fontSize: '14px',
    color: 'rgba(255,255,255,0.35)',
    textAlign: 'center',
    marginBottom: '24px',
  },
  modalForm: {
    display: 'flex',
    flexDirection: 'column',
    gap: '16px',
  },
  modalFooter: {
    textAlign: 'center',
    marginTop: '20px',
    paddingTop: '16px',
    borderTop: '1px solid rgba(255,255,255,0.04)',
  },
  modalFooterText: {
    fontSize: '13px',
    color: 'rgba(255,255,255,0.3)',
  },
  signupLink: {
    color: '#818cf8',
    textDecoration: 'none',
    fontWeight: 500,
    transition: 'color 0.3s ease',
  },

  // ===== FORM ELEMENTS =====
  errorBox: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '12px 16px',
    backgroundColor: 'rgba(239, 68, 68, 0.1)',
    border: '1px solid rgba(239, 68, 68, 0.15)',
    borderRadius: '12px',
    marginBottom: '16px',
  },
  errorIcon: {
    color: '#ef4444',
    fontSize: '16px',
  },
  errorText: {
    color: '#fca5a5',
    fontSize: '14px',
  },
  fieldGroup: {
    display: 'flex',
    flexDirection: 'column',
    gap: '6px',
  },
  label: {
    fontSize: '12px',
    fontWeight: 600,
    color: 'rgba(255,255,255,0.4)',
    textTransform: 'uppercase',
    letterSpacing: '0.3px',
  },
  inputWrapper: {
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
  },
  inputIcon: {
    position: 'absolute',
    left: '14px',
    color: 'rgba(255,255,255,0.15)',
    fontSize: '14px',
    pointerEvents: 'none',
  },
  input: {
    width: '100%',
    padding: '12px 16px 12px 44px',
    backgroundColor: 'rgba(255,255,255,0.04)',
    border: '1px solid rgba(255,255,255,0.06)',
    borderRadius: '12px',
    color: 'white',
    fontSize: '14px',
    fontFamily: 'Inter, sans-serif',
    transition: 'all 0.3s ease',
    outline: 'none',
  },
  toggleBtn: {
    position: 'absolute',
    right: '14px',
    background: 'none',
    border: 'none',
    color: 'rgba(255,255,255,0.15)',
    cursor: 'pointer',
    fontSize: '14px',
    padding: '4px',
    transition: 'color 0.3s ease',
  },
  optionsRow: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: '13px',
  },
  rememberMe: {
    display: 'flex',
    alignItems: 'center',
    gap: '8px',
    color: 'rgba(255,255,255,0.3)',
    cursor: 'pointer',
    transition: 'color 0.3s ease',
  },
  checkbox: {
    width: '16px',
    height: '16px',
    accentColor: '#4f46e5',
    borderRadius: '4px',
    cursor: 'pointer',
    backgroundColor: 'rgba(255,255,255,0.05)',
    border: '1px solid rgba(255,255,255,0.1)',
  },
  forgotLink: {
    color: 'rgba(255,255,255,0.25)',
    fontSize: '13px',
    textDecoration: 'none',
    fontWeight: 500,
    transition: 'color 0.3s ease',
  },
  submitBtn: {
    width: '100%',
    padding: '14px',
    background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
    border: 'none',
    borderRadius: '12px',
    color: 'white',
    fontSize: '15px',
    fontWeight: 600,
    fontFamily: 'Inter, sans-serif',
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '10px',
    boxShadow: '0 4px 20px rgba(79, 70, 229, 0.3)',
  },
  btnIcon: {
    fontSize: '16px',
  },
  spinner: {
    display: 'inline-block',
    width: '18px',
    height: '18px',
    border: '2px solid rgba(255,255,255,0.3)',
    borderTopColor: 'white',
    borderRadius: '50%',
    animation: 'spin 0.8s linear infinite',
  },
};

// Add hover styles
const styleSheet = document.createElement('style');
styleSheet.textContent = `
  .nav-link-hover:hover { color: white !important; }
  .submit-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
  .input-focus:focus { background: rgba(255,255,255,0.06) !important; border-color: #4f46e5 !important; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important; }
  .forgot-link-hover:hover { color: #818cf8 !important; }
  .signup-link-hover:hover { color: #818cf8 !important; }
  .footer-link-hover:hover { color: rgba(255,255,255,0.8) !important; }
  .toggle-hover:hover { color: rgba(255,255,255,0.4) !important; }
  .remember-hover:hover { color: rgba(255,255,255,0.5) !important; }
  .modal-close-hover:hover { background: rgba(255,255,255,0.1) !important; color: white !important; }
  .feature-card-hover:hover { transform: translateY(-4px) !important; border-color: rgba(79, 70, 229, 0.2) !important; box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important; }
  .step-card-hover:hover { transform: translateY(-4px) !important; border-color: rgba(79, 70, 229, 0.15) !important; }
  .hero-primary-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
  .hero-secondary-btn-hover:hover { background: rgba(255,255,255,0.1) !important; border-color: rgba(255,255,255,0.15) !important; }
  .cta-btn-hover:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 40px rgba(79, 70, 229, 0.5) !important; }
  .benefit-card-hover:hover { background: rgba(255,255,255,0.06) !important; transform: translateX(4px) !important; }
  .stat-item-hover:hover { transform: translateY(-4px) !important; }
  .footer-login-btn-hover:hover { background: rgba(79, 70, 229, 0.2) !important; color: #818cf8 !important; }
`;
document.head.appendChild(styleSheet);