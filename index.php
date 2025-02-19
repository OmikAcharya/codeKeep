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
  
    <footer class="footer">
      Made with ❤️ by Om Thanage
    </footer>
    <script src="index.js"></script>
</body>
</html>