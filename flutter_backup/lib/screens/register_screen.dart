import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {
    'email': TextEditingController(),
    'password': TextEditingController(),
    'first_name': TextEditingController(),
    'middle_name': TextEditingController(),
    'last_name': TextEditingController(),
    'suffix': TextEditingController(),
    'contact_number': TextEditingController(),
    'address': TextEditingController(),
    'birth_date': TextEditingController(),
    'organization_name': TextEditingController(),
  };

  String _sex = 'Male';
  String _civilStatus = 'Single';
  String _educationalStatus = 'High School';
  String _employmentStatus = 'Unemployed';
  bool _obscurePassword = true;

  @override
  void dispose() {
    _controllers.forEach((key, controller) => controller.dispose());
    super.dispose();
  }

  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) return;

    // Calculate age from birth date
    final birthDate = DateTime.parse(_controllers['birth_date']!.text);
    final age = DateTime.now().year - birthDate.year;

    final userData = {
      'email': _controllers['email']!.text.trim(),
      'password': _controllers['password']!.text,
      'first_name': _controllers['first_name']!.text.trim(),
      'middle_name': _controllers['middle_name']!.text.trim(),
      'last_name': _controllers['last_name']!.text.trim(),
      'suffix': _controllers['suffix']!.text.trim(),
      'contact_number': _controllers['contact_number']!.text.trim(),
      'address': _controllers['address']!.text.trim(),
      'birth_date': _controllers['birth_date']!.text,
      'age': age,
      'sex': _sex,
      'civil_status': _civilStatus,
      'educational_status': _educationalStatus,
      'employment_status': _employmentStatus,
      'organization_name': _controllers['organization_name']!.text.trim(),
      'youth_classification': [],
      'programs_interested': [],
    };

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.register(userData);

    if (!mounted) return;

    if (success) {
      Navigator.of(context).pushReplacementNamed('/home');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? 'Registration failed'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Register'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Create Account',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0D3B6E),
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                
                _buildTextField('email', 'Email', keyboardType: TextInputType.emailAddress),
                _buildTextField('password', 'Password', obscureText: _obscurePassword, suffixIcon: IconButton(
                  icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility),
                  onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                )),
                _buildTextField('first_name', 'First Name'),
                _buildTextField('middle_name', 'Middle Name', required: false),
                _buildTextField('last_name', 'Last Name'),
                _buildTextField('suffix', 'Suffix (e.g., Jr., III)', required: false),
                _buildTextField('contact_number', 'Contact Number', keyboardType: TextInputType.phone),
                _buildTextField('address', 'Address'),
                _buildDateField(),
                _buildDropdown('Sex', _sex, ['Male', 'Female'], (value) => setState(() => _sex = value!)),
                _buildDropdown('Civil Status', _civilStatus, ['Single', 'Married', 'Widowed', 'Separated'], (value) => setState(() => _civilStatus = value!)),
                _buildDropdown('Educational Status', _educationalStatus, ['Elementary', 'High School', 'College', 'Vocational', 'Graduate'], (value) => setState(() => _educationalStatus = value!)),
                _buildDropdown('Employment Status', _employmentStatus, ['Employed', 'Unemployed', 'Self-employed', 'Student'], (value) => setState(() => _employmentStatus = value!)),
                _buildTextField('organization_name', 'Organization (Optional)', required: false),
                
                const SizedBox(height: 24),
                Consumer<AuthProvider>(
                  builder: (context, authProvider, child) {
                    return ElevatedButton(
                      onPressed: authProvider.isLoading ? null : _register,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1565C0),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: authProvider.isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                color: Colors.white,
                                strokeWidth: 2,
                              ),
                            )
                          : const Text(
                              'Register',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(String key, String label, {
    bool obscureText = false,
    bool required = true,
    TextInputType? keyboardType,
    Widget? suffixIcon,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[key],
        obscureText: obscureText,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          suffixIcon: suffixIcon,
        ),
        validator: (value) {
          if (required && (value == null || value.isEmpty)) {
            return 'Please enter $label';
          }
          return null;
        },
      ),
    );
  }

  Widget _buildDateField() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers['birth_date'],
        readOnly: true,
        decoration: InputDecoration(
          labelText: 'Birth Date',
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          suffixIcon: const Icon(Icons.calendar_today),
        ),
        onTap: () async {
          final date = await showDatePicker(
            context: context,
            initialDate: DateTime(2000),
            firstDate: DateTime(1950),
            lastDate: DateTime.now(),
          );
          if (date != null) {
            _controllers['birth_date']!.text = date.toIso8601String().split('T')[0];
          }
        },
        validator: (value) {
          if (value == null || value.isEmpty) {
            return 'Please select birth date';
          }
          return null;
        },
      ),
    );
  }

  Widget _buildDropdown(String label, String value, List<String> items, ValueChanged<String?> onChanged) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: DropdownButtonFormField<String>(
        value: value,
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        ),
        items: items.map((item) => DropdownMenuItem(value: item, child: Text(item))).toList(),
        onChanged: onChanged,
      ),
    );
  }
}
