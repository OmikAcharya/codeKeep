<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>codeKeep</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <nav>
        <ul class="nav-links">
            <h1>codeKeep</h1>
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#contact">Faqs</a></li>
        </ul>
        <ul class="nav-buttons">
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
        </ul>
    </nav>
    <main>
        <div class="container" id="home">
            <h1 style="color: #02c6f1;">CodeKeep</h1>
            <p style="text-align: center;">
              <span style="font-size: 36px; padding: 0.5rem; font-weight: bold; ">Learn</span>
              <span style="font-size: 36px; padding: 0.5rem; font-weight: bold; ">Practice</span>
              <span style="font-size: 36px; padding: 0.5rem; font-weight: bold; ">Revise</span>
            </p>
            <a href="signup.php"><button class="btn">Get Started for Free</button></a>
        </div>
    </main>
    <section class="stack-area" id="features">
      <div class="left">
        <div class="title">Our Features</div>
        <div class="sub-title">
            Stay ahead with our comprehensive features designed to enhance your competitive programming journey.
            <br />
            <a href="signup.php"><button>See More Details</button></a>
        </div>
      </div>
      <div class="right">
        <div class="card">
            <div class="sub">Contest Calendar</div>
            <div class="content">Keep track of upcoming programming contests effortlessly.</div>
        </div>
        <div class="card">
            <div class="sub">Bookmarked Problems</div>
            <div class="content">Save and revisit important coding problems anytime.</div>
        </div>
        <div class="card">
            <div class="sub">Notes to Remember</div>
            <div class="content">Store important concepts and strategies for quick reference.</div>
        </div>
        <div class="card">
            <div class="sub">Rating Graph</div>
            <div class="content">Visualize your progress and track performance over time.</div>
        </div>
      </div>
    </section>

    <div class="accordion">
    <div class="accordion-item">
        <input type="radio" id="section1" name="accordion" />
        <label for="section1" class="accordion-header">
        <label class="accordion-title">Section 1</label>
        <div class="accordion-icon">
            <svg
            viewBox="0 0 16 16"
            fill="none"
            height="16"
            width="16"
            xmlns="http://www.w3.org/2000/svg"
            >
            <path
                d="M4.293 5.293a1 1 0 0 1 1.414 0L8 7.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 0-1.414z"
                fill="currentColor"
            ></path>
            </svg>
        </div>
        </label>
        <div class="content">
        <p>This is the content for Section 1.</p>
        </div>
    </div>

    <div class="accordion-item">
        <input checked="" type="radio" id="section2" name="accordion" />
        <label for="section2" class="accordion-header">
        <label class="accordion-title">Section 2</label>
        <div class="accordion-icon">
            <svg
            viewBox="0 0 16 16"
            fill="none"
            height="16"
            width="16"
            xmlns="http://www.w3.org/2000/svg"
            >
            <path
                d="M4.293 5.293a1 1 0 0 1 1.414 0L8 7.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 0-1.414z"
                fill="currentColor"
            ></path>
            </svg>
        </div>
        </label>
        <div class="content">
        <p>This is the content for Section 2.</p>
        </div>
    </div>
    <div class="accordion-item">
        <input type="radio" id="section3" name="accordion" />
        <label for="section3" class="accordion-header">
        <label class="accordion-title">Section 3</label>
        <div class="accordion-icon">
            <svg
            viewBox="0 0 16 16"
            fill="none"
            height="16"
            width="16"
            xmlns="http://www.w3.org/2000/svg"
            >
            <path
                d="M4.293 5.293a1 1 0 0 1 1.414 0L8 7.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 0-1.414z"
                fill="currentColor"
            ></path>
            </svg>
        </div>
        </label>
        <div class="content">
        <p>This is the content for Section 3.</p>
        </div>
    </div>
    </div>

  
    <footer class="footer">
      Made with ❤️ by Om Thanage
    </footer>
    <script src="index.js"></script>
</body>
</html>