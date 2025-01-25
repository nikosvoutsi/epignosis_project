<!-- header.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="employee_page.php">Employee Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
               
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');

        navbarToggler.addEventListener('click', function () {
            navbarCollapse.classList.toggle('show');
        });
    });
</script>

<style>
    /* General Navbar Styling */
    .navbar {
        background: linear-gradient(90deg, #1e3c72, #2a5298); /* Gradient background for a modern look */
        border-bottom: 3px solid #ffcc00; /* Gold accent line at the bottom */
        box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.2); /* Subtle shadow for depth */
    }

    .navbar-brand {
        font-size: 1.5rem;
        font-weight: bold;
        color: #ffd700 !important; /* Gold branding for emphasis */
        text-transform: uppercase; /* Uppercase for prominence */
        letter-spacing: 1px;
    }

    /* Navbar Links */
    .navbar-nav .nav-link {
        font-size: 1rem; /* Slightly larger font for readability */
        font-weight: 600;
        color: #ffffff !important; /* White text for clarity */
        padding: 8px 15px; /* Spacing for better usability */
        border-radius: 5px; /* Rounded edges for smoother appearance */
        transition: all 0.3s ease-in-out; /* Smooth transition for hover effects */
    }

    /* Hover Effect */
    .navbar-nav .nav-link:hover {
        color: #1e3c72 !important; /* Dark blue text on hover */
        background-color: #ffcc00; /* Gold background on hover */
    }

    /* Active Link Styling */
    .navbar-nav .nav-link.active {
        color: #ffffff !important; /* White text for active links */
        background-color: #ffcc00 !important; /* Gold background for active links */
        font-weight: bold; /* Make active links stand out */
        border: 2px solid #ffd700; /* Gold border for extra emphasis */
    }

    /* Navbar Toggler */
    .navbar-toggler {
        border: none; /* Remove default border */
        background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent white for visibility */
    }

    .navbar-toggler:hover {
        background-color: #ffcc00; /* Gold background on hover */
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3E%3Cpath stroke='rgba(30, 60, 114, 1)' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E"); /* Custom dark-blue toggler lines */
    }

    /* Responsive Styling */
    @media (max-width: 992px) {
        .navbar-nav .nav-link {
            text-align: center; /* Center links on smaller screens */
            margin: 5px 0; /* Add space between links */
        }
    }
</style>
