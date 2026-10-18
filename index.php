<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeBlood - Donate Blood, Save Lives</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                              url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
        }
        .blood-drop {
            color: #e53e3e;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="font-sans bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <i class="fas fa-tint blood-drop text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-gray-900">LifeBlood</span>
                </div>
            </div>
            <div class="hidden md:ml-6 md:flex md:items-center md:space-x-8">
                <a href="#home" class="text-red-600 font-medium px-3 py-2">Home</a>
                <a href="#about" class="text-gray-700 hover:text-red-600 px-3 py-2">About</a>
                <a href="#donate" class="text-gray-700 hover:text-red-600 px-3 py-2">Donate</a>
                <a href="#inventory" class="text-gray-700 hover:text-red-600 px-3 py-2">Inventory</a>
                <a href="#faq" class="text-gray-700 hover:text-red-600 px-3 py-2">FAQ</a>
                <a href="#contact" class="text-gray-700 hover:text-red-600 px-3 py-2">Contact</a>&nbsp;&nbsp;
                <a href="donor_login.php" class="text-gray-700 hover:text-red-600 px-3 py-2">Donor_Login</a>&nbsp;
                <a href="patient_login.php" class="text-gray-700 hover:text-red-600 px-3 py-2">Patient_Login</a>
                
             <!-- Alternative Dropdown Style -->
<div class="relative group">
    <button class="flex items-center text-gray-700 hover:text-red-600 px-3 py-2 transition-all duration-200 focus:outline-none">
        Register
        <svg class="ml-1 w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div class="absolute right-0 mt-3 w-56 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-100 hidden group-hover:block transition-all duration-300 origin-top transform opacity-0 group-hover:opacity-100 scale-95 group-hover:scale-100">
        <a href="donor_register.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
            <i class="fas fa-hand-holding-heart mr-3 text-red-500 w-5 text-center"></i>
            <span>Donor Register</span>
        </a>
        <a href="register_patients.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
            <i class="fas fa-user-injured mr-3 text-red-500 w-5 text-center"></i>
            <span>Patient Register</span>
        </a>
    </div>
</div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <a href="admin_login.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">Admin_Login</a>
            </div>
            <div class="md:hidden flex items-center">
                <button class="mobile-menu-button">
                    <i class="fas fa-bars text-gray-600 text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile menu -->
    <div class="mobile-menu hidden md:hidden bg-white shadow-lg">
        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Home</a>
        <a href="#about" class="block px-4 py-2 text-gray-700 hover:bg-red-50">About</a>
        <a href="#donate" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Donate</a>
        <a href="#inventory" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Inventory</a>
        <a href="#faq" class="block px-4 py-2 text-gray-700 hover:bg-red-50">FAQ</a>
        <a href="#contact" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Contact</a>
        <a href="donor_login.php" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Donor login</a>
        <a href="patient_login.php" class="block px-4 py-2 text-gray-700 hover:bg-red-50">Patient login</a>
        <div class="border-t border-gray-200"></div>
        <a href="donor_register.php" class="block px-4 py-2 text-gray-700 hover:bg-red-50 font-medium">Donor Register</a>
        <a href="regester_patients.php" class="block px-4 py-2 text-gray-700 hover:bg-red-50 font-medium">Patient Register</a>
        <a href="admin_login.php" class="block px-4 py-2 text-red-600 font-medium">admin_Login</a>
    </div>
</nav>
    <!-- Hero Section -->
    <section id="home"  class="hero-bg text-white py-20 md:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:w-2/3">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Donate Blood, Save Lives</h1>
                <p class="text-xl mb-8">Every 2 seconds someone needs blood. Your donation can make the difference between life and death for patients in need.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#donate" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg text-center transition">Become a Donor</a>
                    <a href="#inventory" class="bg-white hover:bg-gray-100 text-red-600 font-bold py-3 px-6 rounded-lg text-center transition">Blood Availability</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-red-50 p-6 rounded-lg shadow-md text-center transition card-hover">
                    <div class="text-red-600 text-4xl mb-3">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">1 Pint</h3>
                    <p class="text-gray-600">Can save up to 3 lives</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md text-center transition card-hover">
                    <div class="text-red-600 text-4xl mb-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">10 Minutes</h3>
                    <p class="text-gray-600">Average donation time</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md text-center transition card-hover">
                    <div class="text-red-600 text-4xl mb-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">56 Days</h3>
                    <p class="text-gray-600">Between donations</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md text-center transition card-hover">
                    <div class="text-red-600 text-4xl mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">38%</h3>
                    <p class="text-gray-600">Of population eligible</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Donate Blood?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Your blood donation is a lifeline for patients in critical need.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
                         alt="Blood donation" class="rounded-lg shadow-xl w-full h-auto">
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">The Impact of Your Donation</h3>
                    <p class="text-gray-600 mb-6">Blood cannot be manufactured—it can only come from generous donors like you. Every day, blood donors help patients of all ages: accident and burn victims, heart surgery and organ transplant patients, and those battling cancer.</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-red-600"></i>
                            </div>
                            <p class="ml-3 text-gray-700">Safe and simple process that takes less than an hour</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-red-600"></i>
                            </div>
                            <p class="ml-3 text-gray-700">Free health screening with every donation</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-red-600"></i>
                            </div>
                            <p class="ml-3 text-gray-700">You can donate every 8 weeks (whole blood)</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="#donate" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg text-center transition">
                            Donate Now
                        </a>
                        <a href="#faq" class="bg-white hover:bg-gray-100 text-red-600 font-bold py-3 px-6 rounded-lg text-center border border-gray-300 transition">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blood Types Section -->
    <section id="inventory" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Blood Type Information</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Understanding blood types and their importance</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-red-50 p-6 rounded-lg shadow-md transition card-hover">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">A+</h3>
                    <div class="text-center mb-4">
                        <span class="inline-block bg-red-600 text-white px-3 py-1 rounded-full text-sm font-medium">34% of population</span>
                    </div>
                    <p class="text-gray-700 mb-3"><span class="font-bold">Can donate to:</span> A+, AB+</p>
                    <p class="text-gray-700"><span class="font-bold">Can receive from:</span> A+, A-, O+, O-</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md transition card-hover">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">B+</h3>
                    <div class="text-center mb-4">
                        <span class="inline-block bg-red-600 text-white px-3 py-1 rounded-full text-sm font-medium">9% of population</span>
                    </div>
                    <p class="text-gray-700 mb-3"><span class="font-bold">Can donate to:</span> B+, AB+</p>
                    <p class="text-gray-700"><span class="font-bold">Can receive from:</span> B+, B-, O+, O-</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md transition card-hover">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">AB+</h3>
                    <div class="text-center mb-4">
                        <span class="inline-block bg-red-600 text-white px-3 py-1 rounded-full text-sm font-medium">3% of population</span>
                    </div>
                    <p class="text-gray-700 mb-3"><span class="font-bold">Can donate to:</span> AB+</p>
                    <p class="text-gray-700"><span class="font-bold">Can receive from:</span> All blood types</p>
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow-md transition card-hover">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">O+</h3>
                    <div class="text-center mb-4">
                        <span class="inline-block bg-red-600 text-white px-3 py-1 rounded-full text-sm font-medium">38% of population</span>
                    </div>
                    <p class="text-gray-700 mb-3"><span class="font-bold">Can donate to:</span> All Rh+ types</p>
                    <p class="text-gray-700"><span class="font-bold">Can receive from:</span> O+, O-</p>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="#" class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition">
                    View All Blood Types
                </a>
            </div>
        </div>
    </section>

    <!-- Donation Process -->
    <section id="donate" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">The Donation Process</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Simple steps to become a lifesaver</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md transition card-hover">
                    <div class="text-red-600 text-4xl mb-4 text-center">1</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3 text-center">Registration</h3>
                    <p class="text-gray-600 text-center">You'll complete a donor registration form with your basic information.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md transition card-hover">
                    <div class="text-red-600 text-4xl mb-4 text-center">2</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3 text-center">Health Check</h3>
                    <p class="text-gray-600 text-center">We'll check your temperature, pulse, blood pressure and hemoglobin level.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md transition card-hover">
                    <div class="text-red-600 text-4xl mb-4 text-center">3</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3 text-center">Donation</h3>
                    <p class="text-gray-600 text-center">The actual blood donation takes about 8-10 minutes for a whole blood donation.</p>
                </div>
            </div>
            
            <div class="mt-12 bg-red-600 rounded-lg shadow-lg p-8 text-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-2xl font-bold mb-4">Ready to Donate?</h3>
                        <p class="mb-6">Find a donation center near you and schedule your appointment today. Your donation could save a life tomorrow.</p>
                        <a href="#" class="bg-white hover:bg-gray-100 text-red-600 font-bold py-3 px-6 rounded-lg inline-block transition">
                            Find a Center
                        </a>
                    </div>
                    <div class="flex justify-center">
                        <img src="https://images.unsplash.com/photo-1581595219315-a187dd40c322?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
                             alt="Happy donor" class="rounded-lg max-h-64 shadow-xl">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Eligibility -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Who Can Donate?</h2>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Age</h3>
                                <p class="text-gray-600">Between 18-65 years (16-17 with parental consent in some areas)</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Weight</h3>
                                <p class="text-gray-600">At least 110 pounds (50 kg)</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Health</h3>
                                <p class="text-gray-600">Generally in good health and feeling well</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Frequency</h3>
                                <p class="text-gray-600">Every 56 days for whole blood donation</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Before You Donate</h2>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Eat Well</h3>
                                <p class="text-gray-600">Have a healthy meal before your donation</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-glass-water"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Hydrate</h3>
                                <p class="text-gray-600">Drink an extra 16 oz of water before donating</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Bring ID</h3>
                                <p class="text-gray-600">Have your donor card, driver's license or two other forms of ID</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-pills"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Medications</h3>
                                <p class="text-gray-600">Bring a list of medications you're taking</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Common questions about blood donation</p>
            </div>
            
            <div class="max-w-3xl mx-auto">
                <div class="space-y-4">
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button class="faq-question w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">How often can I donate blood?</h3>
                                <i class="fas fa-chevron-down text-red-600 transition-transform"></i>
                            </div>
                        </button>
                        <div class="faq-answer px-6 py-4 bg-gray-50 hidden">
                            <p class="text-gray-600">You can donate whole blood every 56 days (8 weeks). Platelet donors can donate more frequently - up to 24 times per year. Your body replaces the plasma within 24-48 hours and red blood cells in 4-6 weeks.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button class="faq-question w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Does donating blood hurt?</h3>
                                <i class="fas fa-chevron-down text-red-600 transition-transform"></i>
                            </div>
                        </button>
                        <div class="faq-answer px-6 py-4 bg-gray-50 hidden">
                            <p class="text-gray-600">You may feel a slight pinch when the needle is inserted, but most donors report feeling no pain during the donation process. The actual donation typically takes about 8-10 minutes for a whole blood donation.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button class="faq-question w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">How long does it take to donate blood?</h3>
                                <i class="fas fa-chevron-down text-red-600 transition-transform"></i>
                            </div>
                        </button>
                        <div class="faq-answer px-6 py-4 bg-gray-50 hidden">
                            <p class="text-gray-600">The entire process takes about an hour from start to finish. This includes registration, a health history questionnaire, a mini-physical, the donation itself (8-10 minutes), and refreshments afterward.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button class="faq-question w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">What should I do after donating blood?</h3>
                                <i class="fas fa-chevron-down text-red-600 transition-transform"></i>
                            </div>
                        </button>
                        <div class="faq-answer px-6 py-4 bg-gray-50 hidden">
                            <p class="text-gray-600">After donating: Drink extra fluids for 24-48 hours, avoid strenuous physical activity for 5 hours, eat iron-rich foods, and if you feel lightheaded, lie down until the feeling passes. The small bandage can be removed after a few hours.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button class="faq-question w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Can I donate if I have a cold or flu?</h3>
                                <i class="fas fa-chevron-down text-red-600 transition-transform"></i>
                            </div>
                        </button>
                        <div class="faq-answer px-6 py-4 bg-gray-50 hidden">
                            <p class="text-gray-600">No, you should wait until you are feeling well (no fever, sore throat, cough, or congestion) before donating blood. If you're taking antibiotics, you must wait until you've completed the full course of medication.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <a href="#" class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        View All FAQs
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Donor Stories</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Hear from people who have made a difference</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md transition card-hover">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                            <i class="fas fa-user text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Sarah Johnson</h4>
                            <p class="text-gray-600 text-sm">Regular donor for 5 years</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"I've been donating blood every 8 weeks for the past 5 years. It's such a simple way to give back and help others in need. The staff are always friendly and make the process so easy."</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md transition card-hover">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                            <i class="fas fa-user text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Michael Chen</h4>
                            <p class="text-gray-600 text-sm">First-time donor</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"I was nervous about donating for the first time, but the team walked me through every step. It was quick and painless, and I felt great knowing I could help save lives. I'll definitely be back!"</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md transition card-hover">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                            <i class="fas fa-user text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Emma Rodriguez</h4>
                            <p class="text-gray-600 text-sm">Platelet donor</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"After my father needed blood during his cancer treatment, I started donating platelets regularly. It takes a bit longer but helps cancer patients so much. I'm proud to be part of this lifesaving community."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-16 bg-red-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Make a Difference?</h2>
            <p class="text-xl mb-8 max-w-3xl mx-auto">Schedule your blood donation appointment today and become someone's hero.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#" class="bg-white hover:bg-gray-100 text-red-600 font-bold py-3 px-8 rounded-lg transition">
                    Find a Donation Center
                </a>
                <a href="#" class="bg-transparent hover:bg-red-700 border-2 border-white text-white font-bold py-3 px-8 rounded-lg transition">
                    Contact Us
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Contact Us</h2>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Main Donation Center</h3>
                                <p class="text-gray-600">1234 Lifesaver Lane, Suite 100<br>Your City, ST 12345</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Phone</h3>
                                <p class="text-gray-600">(555) 123-4567<br>Mon-Fri: 8am-6pm</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1 text-red-600">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-800">Email</h3>
                                <p class="text-gray-600">info@lifesblood.org<br>We respond within 24 hours</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-red-600 hover:text-red-700 text-2xl">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-700 text-2xl">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-700 text-2xl">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-700 text-2xl">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="bg-gray-50 p-8 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">Send Us a Message</h3>
                        <form>
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-gray-700 font-medium mb-2">Your Name</label>
                                    <input type="text" id="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                </div>
                                <div>
                                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                                    <input type="email" id="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                </div>
                                <div>
                                    <label for="subject" class="block text-gray-700 font-medium mb-2">Subject</label>
                                    <input type="text" id="subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                </div>
                                <div>
                                    <label for="message" class="block text-gray-700 font-medium mb-2">Message</label>
                                    <textarea id="message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="mt-6 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg w-full transition">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-tint blood-drop mr-2"></i>
                        LifeBlood
                    </h3>
                    <p class="text-gray-400">Connecting donors with patients in need to save lives through blood donation.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Home</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition">About</a></li>
                        <li><a href="#donate" class="text-gray-400 hover:text-white transition">Donate</a></li>
                        <li><a href="#inventory" class="text-gray-400 hover:text-white transition">Inventory</a></li>
                        <li><a href="#faq" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Donation Centers</h4>
                    <ul class="space-y-2">
                        <li class="text-gray-400">Downtown Center</li>
                        <li class="text-gray-400">Northside Center</li>
                        <li class="text-gray-400">Westside Center</li>
                        <li class="text-gray-400">Eastside Center</li>
                        <li class="text-gray-400">Mobile Units</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Newsletter</h4>
                    <p class="text-gray-400 mb-4">Subscribe to receive updates on blood drives and urgent needs.</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-lg w-full text-gray-900">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-r-lg transition">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">© 2023 LifeBlood Donation System. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" id="back-to-top" class="fixed bottom-6 right-6 bg-red-600 text-white p-3 rounded-full shadow-lg opacity-0 invisible transition-all duration-300">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');
        
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Back to top button
        const backToTopButton = document.getElementById('back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.remove('opacity-100', 'visible');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });
        
        backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // FAQ accordion
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('i');
                
                // Toggle answer visibility
                answer.classList.toggle('hidden');
                
                // Rotate icon
                icon.classList.toggle('rotate-180');
                
                // Close other open answers
                faqQuestions.forEach(q => {
                    if (q !== question) {
                        q.nextElementSibling.classList.add('hidden');
                        q.querySelector('i').classList.remove('rotate-180');
                    }
                });
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                    
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>