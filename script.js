// Function to toggle password visibility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === "password") {
        input.type = "text"; // Show password
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash"); // Change icon to "crossed eye"
    } else {
        input.type = "password"; // Hide password
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye"); // Change icon back to "eye"
    }
}