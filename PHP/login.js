document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("loginForm")
    .addEventListener("submit", function (event) {
      // Clear previous errors
      document.getElementById("errorContainer").innerHTML = "";

      // Retrieve form values
      var username = document.getElementById("regno").value;
      var email = document.getElementById("email").value;
      var password = document.getElementById("password").value;

      // Perform basic client-side validation
      if (
        username.trim() === "" ||
        email.trim() === "" ||
        password.trim() === ""
      ) {
        document.getElementById("errorContainer").innerHTML =
          '<span class="error-msg">Please fill in all fields.</span>';
        event.preventDefault(); // Prevent form submission
      }
    });
});
