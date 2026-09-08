# LYDO Mobile - Login Screen

## Overview
The login screen for the LYDO (Local Youth Development Office) mobile application built with Flutter. This screen matches the design and branding of the web version.

## Features

### Visual Design
- **Split Layout**: Desktop/tablet view shows a branded left panel and login form on the right
- **Mobile Responsive**: Compact header on mobile devices with scrollable form
- **Brand Colors**: 
  - Primary Blue: `#1565C0`
  - Blue Light: `#1E88E5`
  - Dark Blue: `#0D3B6E`
  - Green: `#2E7D32`
- **Animated Elements**: 
  - Floating shapes on left panel
  - Fade-in and slide-up animations for form
  - Pulsing logo effect

### Form Features
- Email input with validation
- Password input with show/hide toggle
- "Remember Me" checkbox
- "Forgot Password" dialog
- Field validation with error messages
- Loading state during login

### Left Panel Content
- LYDO logo
- Brand name and tagline
- Location information
- Statistics (Youth Members, Programs, Events)
- Footer with copyright

### Interactions
1. **Login**: Validates form and shows loading state (API integration pending)
2. **Forgot Password**: Opens modal dialog for password reset
3. **Register**: Link to navigate to registration screen (pending)
4. **Toggle Password**: Show/hide password visibility

## File Structure
```
lib/
├── screens/
│   ├── login_screen.dart    # Main login screen
│   └── README.md            # This file
└── main.dart                # App entry point
```

## Setup

### 1. Add Logo Asset
Place the LYDO logo at: `assets/images/lydo-logo.png`

You can copy it from the web version:
```bash
# Copy from web version
cp lydo-logo.png lydo_mobile/assets/images/
```

### 2. Install Dependencies
```bash
cd lydo_mobile
flutter pub get
```

### 3. Run the App
```bash
flutter run
```

## Responsive Breakpoints
- **Desktop/Tablet** (width > 768px): Side-by-side layout
- **Mobile** (width ≤ 768px): Stacked layout with compact header

## TODO
- [ ] Integrate with backend API for actual authentication
- [ ] Add proper navigation to home screen after login
- [ ] Implement register screen and navigation
- [ ] Add forgot password functionality with email sending
- [ ] Store "Remember Me" preference locally
- [ ] Add biometric authentication option
- [ ] Implement proper error handling from API
- [ ] Add offline mode detection
- [ ] Add loading skeleton screens

## API Integration Notes

When integrating with the backend API:

1. **Login Endpoint**: POST to `/api/youth/login.php`
2. **Expected Request Body**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

3. **Expected Response**:
```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "Juan Dela Cruz",
    "email": "juan@email.com"
  }
}
```

4. **Implementation Location**: Update `_handleLogin()` method in `login_screen.dart`

## Color Reference
```dart
Primary Blue:     Color(0xFF1565C0)
Blue Light:       Color(0xFF1E88E5)
Blue Dark:        Color(0xFF0D3B6E)
Green:            Color(0xFF2E7D32)
Green Light:      Color(0xFF43A047)
Gray 50:          Color(0xFFF8FAFC)
Gray 100:         Color(0xFFF1F5F9)
Gray 200:         Color(0xFFE2E8F0)
Gray 400:         Color(0xFF94A3B8)
Gray 600:         Color(0xFF475569)
Gray 800:         Color(0xFF1E293B)
Red Error:        Color(0xFFE53935)
```

## Dependencies Used
- `flutter/material.dart` - Material Design components
- `flutter/services.dart` - System services (for future use)

## Notes
- Form validation includes email format checking
- Password must be at least 6 characters
- All animations are performance-optimized
- Supports both light mode (currently implemented)
- Ready for dark mode implementation
