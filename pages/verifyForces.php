<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="duels.css">
    <link rel="stylesheet" href="login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerifyAccount</title>
    <style>
        .twoButtons {
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div style="display: flex; flex-direction: row-reverse; justify-content: space-between; align-items: center;">
            <h1>&lt;Code<span style="color: #1a4eaf">Keep&gt;</span></h1>
            <h1><span style="text-align: left;">Verify CodeForces Account</span></h1>
        </div>
        <form method="POST">
            <div class="form-group">
                <label for="lastName">Enter CodeForces username</label>
                <input required type="email" id="lastName" placeholder="username" name=cfUsername />
            </div>
            <button type="submit" style = "padding-bottom: 10px">Verify</button>
            <div style = "margin-top: 10px"></div>
            <h1>Please submit a compilation error to:</h1>

            <label>(within 3 minutes)</label>
            <div class = "twoButtons">
                <button class = "wowbutton" type="submit" style = "margin: 3px">Retry?</button>
                <button disabled onclick="window.location.href='./duels.php'"class = "wowButton" type="submit" style = "margin: 3px">Continue</button>
            </div>
        </form>
    </div>
</body>
</html>