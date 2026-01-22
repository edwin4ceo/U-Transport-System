<?php
// FUNCTION: START SESSION
session_start();

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// Include Header
include "header.php"; 
?>

<style>
    /* 1. ANIMATION */
    @keyframes fadeInUpPage {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* 2. RESET */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* 3. WRAPPER */
    .faq-wrapper {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 20px;
        background: #f5f7fb; 
        font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 4. HEADER */
    .faq-header-title { text-align: center; margin-bottom: 30px; }
    .faq-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .faq-header-title p { margin: 8px 0 0; font-size: 15px; color: #64748b; }

    /* 5. FAQ CARD STYLING */
    .faq-item {
        background-color: #fff;
        border: 1px solid #f1f5f9; 
        border-radius: 20px;
        margin-bottom: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); 
        overflow: hidden;
        transition: all 0.3s ease; 
    }

    .faq-item:hover {
        transform: translateY(-2px); 
        box-shadow: 0 8px 20px rgba(0, 75, 130, 0.08); 
        border-color: #e0f2fe;
    }

    .faq-question {
        padding: 20px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 15px;
        font-weight: 600;
        color: #333;
        background: #fff;
        user-select: none;
        transition: color 0.3s ease;
    }

    .icon-wrapper {
        width: 28px;
        height: 28px;
        background-color: #f1f5f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .faq-icon {
        font-size: 12px;
        color: #64748b;
        transition: transform 0.3s ease; 
    }

    .faq-answer {
        display: grid;
        grid-template-rows: 0fr; 
        transition: grid-template-rows 0.3s ease-out; 
        background-color: #fff;
    }

    .faq-answer-inner { overflow: hidden; opacity: 0; }
    .faq-answer-content { padding: 0 25px 25px 25px; color: #64748b; line-height: 1.6; font-size: 14px; }

    .faq-item.active { border-color: #004b82; box-shadow: 0 4px 15px rgba(0, 75, 130, 0.1); }
    .faq-item.active .faq-question { color: #004b82; }
    .faq-item.active .icon-wrapper { background-color: #004b82; transform: rotate(180deg); }
    .faq-item.active .faq-icon { color: #fff; }
    .faq-item.active .faq-answer { grid-template-rows: 1fr; }
    .faq-item.active .faq-answer-inner { opacity: 1; transition: opacity 0.3s ease 0.1s; }

    /* ========================================================= */
    /* 6. NEW: ABOUT US & FOUNDERS SECTION                       */
    /* ========================================================= */
    .about-section { 
        margin-top: 60px; 
        padding-top: 50px; 
        border-top: 2px dashed #e2e8f0; 
        text-align: center; 
    }
    .about-section h2 { color: #004b82; font-size: 26px; font-weight: 800; margin-bottom: 40px; }

    .founders-grid { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px; 
        margin-bottom: 50px; 
    }
    .founder-card { text-align: center; }
    .founder-img-wrap { 
        width: 130px; height: 130px; 
        margin: 0 auto 15px; 
        border-radius: 50%; 
        border: 4px solid #fff; 
        box-shadow: 0 8px 20px rgba(0,75,130,0.12); 
        overflow: hidden; 
        transition: 0.3s ease;
        background: #fff;
    }
    .founder-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .founder-card:hover .founder-img-wrap { transform: scale(1.05); border-color: #004b82; }
    .founder-name { font-weight: 700; color: #1e293b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* MISSION BOXES */
    .mission-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
        gap: 20px; 
        margin-top: 30px; 
    }
    .mission-box { 
        background: #fff; padding: 30px 20px; 
        border-radius: 20px; border: 1px solid #f1f5f9; 
        transition: all 0.3s ease; 
    }
    .mission-box:hover { transform: translateY(-5px); border-color: #004b82; box-shadow: 0 10px 25px rgba(0,75,130,0.05); }
    .mission-box i { font-size: 32px; color: #004b82; margin-bottom: 15px; }
    .mission-box h3 { font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
    .mission-box p { font-size: 13px; color: #64748b; line-height: 1.6; margin: 0; }

    /* 7. CONTACT BOX */
    .contact-box {
        text-align: center; 
        margin-top: 60px; 
        padding: 40px 30px; 
        background: #fff; 
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .contact-box h3 { margin: 0 0 8px; color: #004b82; font-size: 18px; font-weight: 700; }
    .contact-box p { margin: 0 0 25px; color: #64748b; font-size: 14px; }

    .btn-contact { 
        display: inline-block !important;
        width: auto !important;             
        min-width: 200px !important;        
        padding: 12px 40px !important;      
        background-color: #004b82 !important; 
        color: white !important; 
        border: none !important; 
        border-radius: 50px !important;     
        font-size: 15px !important; 
        font-weight: 600 !important; 
        text-align: center !important;
        text-decoration: none !important;   
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
    }
    .btn-contact:hover { 
        background-color: #003660 !important; 
        transform: translateY(-2px); 
    }

    @media (max-width: 768px) {
        .founders-grid { grid-template-columns: 1fr; gap: 30px; }
        .btn-contact { display: block !important; width: 100% !important; }
    }
</style>

<div class="faq-wrapper">
    
    <div class="faq-header-title">
        <h1>Frequently Asked Questions</h1>
        <p>Common questions about using U-Transport.</p>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            What is the U-Transport System?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    U-Transport is a web-based platform designed exclusively for MMU Malacca students. 
                    It allows students to search for rides offered by other students (drivers) within the campus community, 
                    providing a safer and more centralized alternative to social media groups.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Who can use this system?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    Access is strictly limited to <strong>registered students and staff of MMU Malacca Campus</strong>. 
                    You must have a valid university email address to register and login.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Is this service safe?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    Yes. All drivers must undergo a <strong>manual verification process</strong> 
                    by the admin, submitting their Student ID and Driving License before they can post rides.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            How do I make a payment?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    The system does not process online payments. All payments are made <strong>offline directly to the driver</strong>. 
                    You can pay via Cash or DuitNow QR transfer as agreed upon with your driver.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Is there a mobile app?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    No. U-Transport is a web-based system accessible through a modern web browser on PC or Mobile. 
                    There is no native app on the App Store or Play Store.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Can I track the driver's location?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    No, the system does not support real-time GPS tracking. However, once a booking is confirmed, 
                    you will receive the driver's contact number to coordinate directly.
                </div>
            </div>
        </div>
    </div>

    <div class="about-section">
        <h2>Meet Our Founders</h2>
        
        <div class="founders-grid">
            <div class="founder-card">
                <div class="founder-img-wrap">
                    <img src="uploads/EDWIN.jpg" alt="Edwin Teo Yuan Jing">
                </div>
                <div class="founder-name">Edwin Teo Yuan Jing</div>
            </div>

            <div class="founder-card">
                <div class="founder-img-wrap">
                    <img src="uploads/Wong.jpg" alt="Wong Soon Kit">
                </div>
                <div class="founder-name">Wong Soon Kit</div>
            </div>

            <div class="founder-card">
                <div class="founder-img-wrap">
                    <img src="uploads/NG.jpg" alt="Ng Zhe Jun">
                </div>
                <div class="founder-name">Ng Zhe Jun</div>
            </div>
        </div>

        <h2>Our Mission</h2>
        <div class="mission-grid">
            <div class="mission-box">
                <i class="fa-solid fa-shield-halved"></i>
                <h3>Safety First</h3>
                <p>Creating a verified environment for MMU community through strict domain authentication and manual driver checks.</p>
            </div>
            <div class="mission-box">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <h3>Affordability</h3>
                <p>Ensuring cost-effective commuting for students while providing student-drivers a way to manage maintenance costs.</p>
            </div>
            <div class="mission-box">
                <i class="fa-solid fa-users"></i>
                <h3>Community Driven</h3>
                <p>Consolidating scattered carpooling efforts into a centralized platform to improve logistics and mutual trust.</p>
            </div>
        </div>
    </div>

    <div class="contact-box">
        <h3>Still have questions?</h3>
        <p>Can't find the answer you're looking for? Please chat to our friendly team.</p>
        
        <a href="contact_us.php" class="btn-contact">
            Contact Support
        </a>
    </div>

</div>

<script>
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close others
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            // Toggle current
            item.classList.toggle('active');
        });
    });
</script>

<?php include "footer.php"; ?>