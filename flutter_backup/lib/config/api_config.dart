class ApiConfig {
  // Change this to your local IP address (same WiFi network)
  // Find your IP: Windows CMD -> ipconfig -> IPv4 Address
  static const String baseUrl = 'http://192.168.1.11/LYDO/lydo-system/api/youth';
  
  // Endpoints
  static const String login = '$baseUrl/login.php';
  static const String register = '$baseUrl/register.php';
  static const String logout = '$baseUrl/logout.php';
  static const String profile = '$baseUrl/profile.php';
  static const String dashboard = '$baseUrl/dashboard.php';
  static const String events = '$baseUrl/events.php';
  static const String notifications = '$baseUrl/notifications.php';
  static const String merit = '$baseUrl/merit.php';
  static const String wellbeing = '$baseUrl/wellbeing.php';
  
  // Upload base URL
  static const String uploadBaseUrl = 'http://192.168.1.11/LYDO/lydo-system/shared/uploads/event_photos';
}
