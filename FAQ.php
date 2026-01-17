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
    /* ========================================= */
    /* 1. PAGE ENTRANCE ANIMATION (NEW!)         */
    /* ========================================= */
    @keyframes fadeInUpPage {
        0% {
            opacity: 0;
            transform: translateY(60px); /* Start 60px below */
        }
        100% {
            opacity: 1;
            transform: translateY(0);    /* End at normal position */
        }
    }

    /* 2. CONTAINER LAYOUT */
    .faq-container {
        max-width: 800px;
        margin: 0 auto;
        padding-bottom: 100px;
        font-family: 'Poppins', sans-serif;
        
        /* Apply the animation here */
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 3. TITLE DESIGN (Capsule Style) */
    .page-title {
        text-align: center;
        margin-bottom: 60px; 
        position: relative;  
        color: #005A9C;      
        font-weight: 700;    
        font-size: 36px;     
        padding-bottom: 15px; 
    }

    .page-title::after {
        content: "";
        position: absolute;
        bottom: 0;           
        left: 50%;           
        transform: translateX(-50%); 
        width: 120px;        
        height: 4px;         
        background-color: #005A9C; 
        border-radius: 10px; 
        opacity: 0.8;        
    }

    /* 4. FAQ CARD STYLING */
    .faq-item {
        background-color: #fff;
        border: 1px solid #eef2f6; 
        border-radius: 16px;       
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
        overflow: hidden;
        transition: all 0.3s ease; 
    }

    .faq-item:hover {
        transform: translateY(-2px); 
        box-shadow: 0 8px 25px rgba(0, 90, 156, 0.08); 
        border-color: #dbeafe;
    }

    /* 5. QUESTION HEADER */
    .faq-question {
        padding: 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 17px;
        font-weight: 600;
        color: #333;
        background: #fff;
        user-select: none;
        transition: color 0.3s ease;
    }

    /* Icon styling */
    .icon-wrapper {
        width: 32px;
        height: 32px;
        background-color: #f1f5f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .faq-icon {
        font-size: 14px;
        color: #64748b;
        transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55); 
    }

    /* 6. ANSWER ANIMATION (Inner Slide) */
    .faq-answer {
        display: grid;
        grid-template-rows: 0fr; 
        transition: grid-template-rows 0.4s ease-out; 
        background-color: #fff;
    }

    .faq-answer-inner {
        overflow: hidden;
        opacity: 0; 
        transform: translateY(20px); 
        transition: opacity 0.4s ease, transform 0.4s ease; 
    }

    .faq-answer-content {
        padding: 0 25px 25px 25px;
        color: #555;
        line-height: 1.7;
        font-size: 15px;
    }

    /* 7. ACTIVE STATE */
    .faq-item.active {
        border-color: #005A9C; 
        box-shadow: 0 8px 30px rgba(0, 90, 156, 0.12);
    }

    .faq-item.active .faq-question {
        color: #005A9C; 
    }

    .faq-item.active .icon-wrapper {
        background-color: #005A9C; 
        transform: rotate(180deg);
    }

    .faq-item.active .faq-icon {
        color: #fff; 
    }

    .faq-item.active .faq-answer {
        grid-template-rows: 1fr; 
    }

    .faq-item.active .faq-answer-inner {
        opacity: 1;
        transform: translateY(0); 
        transition-delay: 0.1s;   
    }

    /* 8. CONTACT BOX */
    .contact-box {
        text-align: center; 
        margin-top: 60px; 
        padding: 40px; 
        background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); 
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    }
</style>

<div class="faq-container">
    
    <h2 class="page-title">Frequently Asked Questions</h2>

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
                    You must have a valid university email address to register and login. Students from other campuses cannot access this system.
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
                    Yes. Safety is our priority. All drivers must undergo a <strong>manual verification process</strong> 
                    by the admin, submitting their Student ID and Driving License before they can post rides. 
                    Additionally, the passenger review system helps maintain high service standards.
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
            Is there a mobile app I can download?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    No. U-Transport is a web-based system accessible through a modern web browser (Chrome, Safari, etc.) 
                    on PC or Laptop. There is no native app on the App Store or Play Store.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Can I track the driver's location in real-time?
            <div class="icon-wrapper"><i class="fa-solid fa-chevron-down faq-icon"></i></div>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <div class="faq-answer-content">
                    No, the system does not support real-time GPS tracking. However, once a booking is confirmed, 
                    you will receive the driver's contact number to coordinate the pickup location directly.
                </div>
            </div>
        </div>
    </div>

    <div class="contact-box">
        <h3 style="margin-bottom: 10px; color: #005A9C; margin-top:0; font-size: 24px;">Still have questions?</h3>
        <p style="margin-bottom: 30px; color: #64748b;">Can't find the answer you're looking for? Please chat to our friendly team.</p>
        <a href="contact_us.php" style="background-color: #005A9C; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: 600; box-shadow: 0 10px 20px rgba(0,90,156,0.25); transition: all 0.3s;">
            Contact Support
        </a>
    </div>

</div>

<script>
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Logic: Close other items when opening a new one (Accordion style)
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });

            // Toggle the clicked item
            item.classList.toggle('active');
        });
    });
</script>

<?php include "footer.php"; ?>