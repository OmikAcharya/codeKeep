<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeCase</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <nav>
        <ul class="nav-links">
            <h1>CodeCase</h1>
        </ul>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#contact">Contact Us</a></li>
        </ul>
        <ul class="nav-buttons">
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign Up</a></li>
        </ul>
    </nav>

    <!-- Merged Hero Section with new Klu-inspired design -->
    <main class="hero-section" id="home">
        <div class="container">
            <h1 class="hero-title">Learn, Practice & Compete</h1>
            
            <p>Connect all your competitive programming resources in one place.</p>
            
            
            <button class="cta-button">TRY CODECASE NOW</button>
            
            <div class="platforms">
                <div class="platform-icon" title="Codeforces">CF</div>
                <div class="platform-icon" title="LeetCode">LC</div>
                <div class="platform-icon" title="CodeChef">CC</div>
            </div>
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
  
    <script src="index.js"></script>
</body>
</html>