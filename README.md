# 🕉️ Divya Yatra Express - Temple Pilgrimage Bus Booking System

A full-stack, responsive **Bus Booking Management System** for visiting a sacred **Temple (Fixed Destination)**, designed with an authentic **Hindu Devotional Theme** and a seamless **BookMyShow-style user workflow**.

Built with **PHP 8.2**, **MySQL (MariaDB)**, **Bootstrap 5**, **JavaScript**, and **CSS**.

---

## 🚩 BookMyShow Workflow & Hindu Devotional Theme

### 1. Section 1: Devotional Hero Section (Fully Managed from Admin Panel)
- **Spiritual Badge**: Holy mantra banner (e.g. `ॐ Om Namo Bhagavate Vasudevaya • Daily Temple Special`).
- **Hero Title & Subtitle**: Highlighting the sacred pilgrimage yatra to the predefined temple destination.
- **Dynamic Link & Button**: Customizable button text and link (e.g., `Book Yatra Bus Now` linking to `#available-buses` or custom link), fully editable in **Admin Panel** (`admin/destination.php`).
- **Temple Destination & Darshan Timings**: Prominently shows temple name, arrival gopuram/landmark, address, helpline, and morning/evening darshan schedules.

### 2. Section 2: Available Buses (Cinema/Movie Card Style like BookMyShow)
- **Visual Bus Cards with Poster Images**: Each bus has its own poster image, star rating badge (`★ 4.9/5`), bus class (`Multi-Axle AC Airavat`, `Pushback Deluxe`), and amenities tags (`Bhajans & Chants`, `AC`, `Water Bottle`, `Mobile Charger`).
- **Date Filter Pills**: Quick date selection slider (`Today`, `Tomorrow`, upcoming dates, and calendar picker).
- **Pricing & Seats**: Live `₹ 250 on-wards` fare indicator and real-time remaining seat capacity badge.
- **Card Click Navigation**: Clicking anywhere on a bus card smoothly opens the dedicated **Bus Details & Showtimes Page** (`bus-details.php?id=...&date=...`).

### 3. Dedicated Bus & Trip Showtimes Page (`bus-details.php`)
- Large cinema-style header showcasing the selected bus, high-res photo, rating, registration number, amenities, and temple destination.
- Date selector bar to view schedules for any day.
- **Trip Showtimes List**: Displays departure time badge (`07:30 AM`), seat availability bar, occupancy %, boarding stops, and prominent **"Book Seat"** button on each departure time.

### 4. Seat Selection, Devotee Names, Mobile OTP & Payment (`book.php`)
- **Step 1: How Many Seats / Devotees?**: Quick BookMyShow-style seat number buttons (`1`, `2`, `3`, `4`, `5`, `6+`).
- **Step 2: Boarding / Pickup Point**: Select nearest stop along the direct route.
- **Step 3: Names of Travelling Devotees**: **Dynamically generates** input fields for each passenger name based on the selected seat count!
- **Step 4: Contact Mobile & OTP**: Enter mobile number $\rightarrow$ Click `Send OTP` $\rightarrow$ Enter 6-digit verification code $\rightarrow$ Verified!
- **Step 5: Secure Online Payment**: Live fare breakdown $\rightarrow$ Interactive Payment Gateway (Cards, UPI / QR Code, Net Banking) or Razorpay $\rightarrow$ Instant verification!
- **Step 6: Digital Sacred Yatra Pass**: Generates official booking pass with reference number, barcode, devotee names, pickup reporting time, and SMS/WhatsApp confirmation.

### 2.2 Bus Fleet Management (Admin)
- Add, edit, and activate/deactivate buses.
- Configure bus model/name and registration numbers.
- Set total seat capacity and default rate per seat.
- Manage trip dates and booking availability.
- View total booked seats, remaining capacity, and cumulative fleet occupancy %.

### 2.3 Pickup Point Management (Admin)
- Add, edit, and toggle status (active/inactive) for pickup points.
- Configure pickup name, prominent landmarks, full address, and display route order.
- Dynamically displayed during customer checkout.

### 2.4 Predefined Fixed Destination (Admin)
- Single fixed destination configuration panel.
- Configurable Destination Name, Transit Bay / Landmark, Address, and Helpline Contact.
- Multiple destination complexity excluded as specified.

### 2.5 Online Payment Gateway
- Supports Sandbox Payment Gateway Simulator (Cards, UPI, Net Banking) for test environments without third-party fees.
- Ready for live Razorpay Checkout and signature verification.
- Enforces payment verification before confirming bookings.
- Maintains transaction references and status history (`initiated`, `success`, `failed`, `refunded`).
- Automatically updates trip booked seats upon verified payment.

### 2.6 Customer OTP Login & Trip History
- Mobile number OTP login and guest booking support.
- View upcoming and previous trip reservations.
- View trip date, pickup point, number of seats booked, total paid amount, payment status, and booking reference.
- Customer-initiated booking cancellation with automated seat restoration and notifications.

### 2.7 SMS & WhatsApp Notifications
- **Customer Notifications**:
  - OTP verification codes (10-minute expiry).
  - Booking confirmation alerts (with trip date, departure time, pickup point, destination, reference, and amount).
  - Cancellation alerts and refund status.
- **Administrator Notifications**:
  - Instant new booking & payment confirmation alerts.
  - Booking cancellation alerts.
  - **70% Seat Occupancy Congestion Alerts**.
- **Supported Providers**:
  - Zero-cost Simulation Mode (logs all notifications to MySQL and displays live toast popups for easy demonstration).
  - Live API integration for Fast2SMS, Twilio SMS, and Meta WhatsApp Cloud API.

### 2.8 70% Seat Occupancy Alert Engine
- **Formula**:
  $$\text{Occupancy Percentage} = \left(\frac{\text{Total Booked Seats}}{\text{Total Bus Capacity}}\right) \times 100$$
- Triggers an automated priority alert to the Administrator via SMS and WhatsApp whenever a trip reaches or exceeds 70% occupancy.
- **Duplicate Prevention**: Protected by the `occupancy_alert_sent` flag on each trip to guarantee alerts fire **only once** per threshold crossing.

### 2.9 Admin Dashboard & Reports
- **Real-Time KPIs**: Total Confirmed Bookings, Total Seats Booked, Total Revenue, Available Seat Capacity, Active Fleet, and Upcoming Trips.
- **70% Occupancy Monitor**: Dedicated alert card highlighting trips currently at or above 70% capacity.
- **Exportable Reports**:
  - Date-Range Sales Reports
  - Trip-wise Occupancy & Threshold Reports
  - Bus-wise Performance Reports
  - Payment Success, Failure & Refund Reports
  - Customer Booking History Directory
- **1-Click CSV Export**: Instant table export to Excel-compatible CSV files.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, Google Fonts (Plus Jakarta Sans).
- **Backend**: PHP 8.2+ (PDO MySQL, Sessions, REST JSON APIs).
- **Database**: MySQL 5.7+ / MariaDB 10.4+.
- **Payment Gateway**: Razorpay & Built-in Sandbox Interactive Checkout Simulator.
- **Messaging**: Fast2SMS, Twilio, Meta WhatsApp Cloud API, and Built-in Notification Simulation Engine.

---

## 🚀 Quick Setup & Installation

### 1. Requirements
- XAMPP / WAMP / LAMP with PHP 8.0+ and MySQL running.
- Document Root directory: `c:\xampp\htdocs\bus` (or your web server directory).

### 2. Automatic One-Click Database Installation
1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open your browser and navigate to:
   ```
   http://localhost/bus/install.php?run=1
   ```
   *Or run via command line:*
   ```bash
   php install.php
   ```
3. The database `bus_booking_db` will be created automatically with all tables, initial buses, pickup stops, and 7 days of scheduled trips!

### 3. Default Credentials
- **Customer Portal**: Any 10-digit mobile number (e.g., `9876543210`). In simulation mode, the OTP is displayed immediately on-screen and pre-filled for convenience!
- **Admin Portal**:
  - **URL**: `http://localhost/bus/admin/login.php`
  - **Username**: `admin`
  - **Password**: `admin123`

---

## 📂 Project Directory Structure

```text
c:\xampp\htdocs\bus\
├── config/
│   ├── config.php            # Dynamic settings loader & constants
│   └── db.php                # PDO Database connection & auto-creation
├── includes/
│   ├── auth.php              # Customer OTP & Admin authentication
│   ├── functions.php         # Helpers (formatting, calculations, flash messages)
│   ├── notification.php      # SMS & WhatsApp engine + 70% threshold trigger
│   ├── payment.php           # Online payment processor & transaction verifier
│   ├── header.php            # Customer navigation & destination banner
│   └── footer.php            # Customer footer & scripts
├── admin/
│   ├── index.php             # Admin Dashboard (KPIs, 70% occupancy alerts, trips)
│   ├── login.php             # Admin login
│   ├── logout.php            # Admin logout
│   ├── buses.php             # Bus fleet management
│   ├── trips.php             # Trip scheduling & occupancy tracker
│   ├── pickups.php           # Boarding pickup points management
│   ├── bookings.php          # Booking sales, ticket vouchers & cancellations
│   ├── reports.php           # 5 Comprehensive reports with CSV exports
│   ├── notifications.php     # SMS & WhatsApp audit trail & alert tester
│   ├── destination.php       # Predefined fixed destination settings
│   ├── settings.php          # Gateway keys, alert numbers & threshold %
│   └── includes/
│       ├── header.php        # Admin sidebar & topbar
│       └── footer.php        # Admin footer
├── api/
│   ├── send_otp.php          # Customer OTP dispatcher
│   ├── verify_otp.php        # Customer OTP verification
│   ├── calculate_price.php   # Price calculation API
│   ├── create_booking.php    # Pending booking order creation
│   └── verify_payment.php    # Payment verification & booking completion
├── assets/
│   ├── css/
│   │   ├── style.css         # Customer responsive stylesheet
│   │   └── admin.css         # Admin dashboard stylesheet
│   └── js/
│       ├── main.js           # Customer booking flow, OTP & payment modal
│       └── admin.js          # Admin sidebar & CSV export utilities
├── tests/
│   └── test_workflow.php     # Automated end-to-end integration test suite
├── index.php                 # Customer homepage & booking interface
├── my-bookings.php           # Customer OTP portal & trip history
├── booking-success.php       # Instant booking confirmation & ticket
├── ticket.php                # Printable e-ticket boarding voucher
├── install.php               # One-click installer & database migrator
└── README.md                 # Complete documentation & guide
```

---

## 🧪 Testing the 70% Occupancy Alert

1. Go to **Admin Portal** -> **Trips & Occupancy** (`http://localhost/bus/admin/trips.php`).
2. Each trip shows its live occupancy:
   $$\text{Occupancy \%} = \left(\frac{\text{Booked Seats}}{\text{Total Capacity}}\right) \times 100$$
3. When bookings cause a trip to reach $\ge 70\%$:
   - The system triggers an instant SMS and WhatsApp alert to the Admin phone (`+919876543210`).
   - The alert is logged in **SMS & WhatsApp Logs** (`admin/notifications.php`).
   - The trip appears in the **70% Seat Occupancy Alert Monitor** on the Admin Dashboard.
   - The flag `occupancy_alert_sent = 1` prevents repeated notifications for the same trip.
4. You can also run the automated test suite at any time:
   ```bash
   php tests/test_workflow.php
   ```

---

## 📜 Handover & Administrator Training

1. **Managing Buses**:
   - Access `admin/buses.php` to add new buses or change total capacity.
2. **Scheduling Trips**:
   - Access `admin/trips.php` to schedule trips for any future date with departure times and seat rates.
3. **Updating Fixed Destination**:
   - Access `admin/destination.php` to change the destination campus/city, address, or phone number.
4. **Exporting Reports**:
   - Access `admin/reports.php` to filter date ranges and download Excel-compatible CSV reports with a single click.
