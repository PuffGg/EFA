function validateForm() {
  var email = document.getElementById("email").value.trim();
  var password = document.getElementById("password").value;
  var cpassword = document.getElementById("cpassword").value;

  // Regular expression to match the email format xxx@gmail.com
  var emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;

  // Regular expression to match password with at least 8 characters including letters and numbers
  var passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

  if (!emailRegex.test(email)) {
    alert("Email must be in the format example@gmail.com");
    return false;
  }

  if (!passwordRegex.test(password)) {
    alert(
      "Password must be at least 8 characters long and include both letters and numbers"
    );
    return false;
  }

  if (password !== cpassword) {
    alert("Passwords do not match!");
    return false;
  }

  return true;
}

function showLoadingBar() {
  document.getElementById("loading-bar").style.display = "block";
}

function hideLoadingBar() {
  document.getElementById("loading-bar").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function () {
  // Listen for form submission
  document.querySelector("form").addEventListener("submit", function (event) {
    // Show loading bar when form is submitted
    if (!validateForm()) {
      event.preventDefault(); // Prevent form submission if validation fails
      return;
    }

    document.querySelector("form").onsubmit = function() {
      const loadingBar = document.getElementById("loading-bar");
      loadingBar.style.width = "0";
      loadingBar.style.transition = "width 2s ease-in-out";
      loadingBar.style.width = "100%";
  };
  
  });
});
