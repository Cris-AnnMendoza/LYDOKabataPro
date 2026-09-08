import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import '../models/user.dart';

class ApiService {
  String? _token;

  // Initialize token from storage
  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
  }

  // Save token to storage
  Future<void> _saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  // Clear token from storage
  Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  // Check if user is logged in
  bool get isLoggedIn => _token != null && _token!.isNotEmpty;

  // Get auth headers
  Map<String, String> _getHeaders({bool includeAuth = true}) {
    final headers = {'Content-Type': 'application/json'};
    if (includeAuth && _token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }

  // Login
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse(ApiConfig.login),
      headers: _getHeaders(includeAuth: false),
      body: jsonEncode({'email': email, 'password': password}),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      await _saveToken(data['token']);
      return data;
    } else {
      throw Exception(data['error'] ?? 'Login failed');
    }
  }

  // Register
  Future<Map<String, dynamic>> register(Map<String, dynamic> userData) async {
    final response = await http.post(
      Uri.parse(ApiConfig.register),
      headers: _getHeaders(includeAuth: false),
      body: jsonEncode(userData),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      await _saveToken(data['token']);
      return data;
    } else {
      throw Exception(data['error'] ?? 'Registration failed');
    }
  }

  // Logout
  Future<void> logout() async {
    if (_token != null) {
      try {
        await http.post(
          Uri.parse(ApiConfig.logout),
          headers: _getHeaders(),
        );
      } catch (e) {
        // Ignore errors during logout
      }
    }
    await clearToken();
  }

  // Get profile
  Future<User> getProfile() async {
    final response = await http.get(
      Uri.parse(ApiConfig.profile),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return User.fromJson(data['user']);
    } else {
      throw Exception(data['error'] ?? 'Failed to load profile');
    }
  }

  // Update profile
  Future<User> updateProfile(Map<String, dynamic> updates) async {
    final response = await http.put(
      Uri.parse(ApiConfig.profile),
      headers: _getHeaders(),
      body: jsonEncode(updates),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return User.fromJson(data['user']);
    } else {
      throw Exception(data['error'] ?? 'Failed to update profile');
    }
  }

  // Get dashboard stats
  Future<Map<String, dynamic>> getDashboard() async {
    final response = await http.get(
      Uri.parse(ApiConfig.dashboard),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Failed to load dashboard');
    }
  }

  // Get events (filter: upcoming | history | all)
  Future<List<dynamic>> getEvents(String filter) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.events}?filter=$filter'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return filter == 'history' ? data['history'] : data['events'];
    } else {
      throw Exception(data['error'] ?? 'Failed to load events');
    }
  }

  // Check in to event
  Future<Map<String, dynamic>> checkIn(String code, String? photoBase64) async {
    final body = {
      'action': 'checkin',
      'code': code,
      if (photoBase64 != null) 'photo': photoBase64,
    };

    final response = await http.post(
      Uri.parse(ApiConfig.events),
      headers: _getHeaders(),
      body: jsonEncode(body),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Check-in failed');
    }
  }

  // Check out of event
  Future<Map<String, dynamic>> checkOut(String code, String? photoBase64) async {
    final body = {
      'action': 'checkout',
      'code': code,
      if (photoBase64 != null) 'photo': photoBase64,
    };

    final response = await http.post(
      Uri.parse(ApiConfig.events),
      headers: _getHeaders(),
      body: jsonEncode(body),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Check-out failed');
    }
  }

  // Get notifications
  Future<List<dynamic>> getNotifications({int limit = 50}) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.notifications}?limit=$limit'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data['notifications'];
    } else {
      throw Exception(data['error'] ?? 'Failed to load notifications');
    }
  }

  // Mark notification as read
  Future<void> markNotificationRead(int notificationId) async {
    await http.post(
      Uri.parse(ApiConfig.notifications),
      headers: _getHeaders(),
      body: jsonEncode({'notification_id': notificationId}),
    );
  }

  // Mark all notifications as read
  Future<void> markAllNotificationsRead() async {
    await http.post(
      Uri.parse(ApiConfig.notifications),
      headers: _getHeaders(),
      body: jsonEncode({'mark_all': true}),
    );
  }

  // Get merit logs
  Future<Map<String, dynamic>> getMeritLogs({int limit = 50}) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.merit}?limit=$limit'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Failed to load merit logs');
    }
  }

  // Get wellbeing chat history
  Future<List<dynamic>> getWellbeingHistory({int limit = 50}) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.wellbeing}?limit=$limit'),
      headers: _getHeaders(),
    );

    if (response.statusCode == 401) {
      throw Exception('Unauthorized');
    }

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data['history'];
    } else {
      throw Exception(data['error'] ?? 'Failed to load chat history');
    }
  }

  // Send wellbeing message
  Future<Map<String, dynamic>> sendWellbeingMessage(String message) async {
    final response = await http.post(
      Uri.parse(ApiConfig.wellbeing),
      headers: _getHeaders(),
      body: jsonEncode({'message': message}),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Failed to send message');
    }
  }
}
