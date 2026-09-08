import 'dart:convert';

class User {
  final int id;
  final String email;
  final String firstName;
  final String middleName;
  final String lastName;
  final String suffix;
  final String contactNumber;
  final String address;
  final String birthDate;
  final int age;
  final String sex;
  final String civilStatus;
  final String educationalStatus;
  final String employmentStatus;
  final String organizationName;
  final List<String> youthClassification;
  final List<String> programsInterested;
  final String createdAt;

  User({
    required this.id,
    required this.email,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.suffix,
    required this.contactNumber,
    required this.address,
    required this.birthDate,
    required this.age,
    required this.sex,
    required this.civilStatus,
    required this.educationalStatus,
    required this.employmentStatus,
    required this.organizationName,
    required this.youthClassification,
    required this.programsInterested,
    required this.createdAt,
  });

  String get fullName => '$firstName $middleName $lastName $suffix'.trim();

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: int.parse(json['id'].toString()),
      email: json['email'] ?? '',
      firstName: json['first_name'] ?? '',
      middleName: json['middle_name'] ?? '',
      lastName: json['last_name'] ?? '',
      suffix: json['suffix'] ?? '',
      contactNumber: json['contact_number'] ?? '',
      address: json['address'] ?? '',
      birthDate: json['birth_date'] ?? '',
      age: int.parse(json['age']?.toString() ?? '0'),
      sex: json['sex'] ?? '',
      civilStatus: json['civil_status'] ?? '',
      educationalStatus: json['educational_status'] ?? '',
      employmentStatus: json['employment_status'] ?? '',
      organizationName: json['organization_name'] ?? '',
      youthClassification: _parseJsonArray(json['youth_classification']),
      programsInterested: _parseJsonArray(json['programs_interested']),
      createdAt: json['created_at'] ?? '',
    );
  }

  static List<String> _parseJsonArray(dynamic value) {
    if (value == null || value == '') return [];
    if (value is String) {
      try {
        final decoded = json.decode(value);
        if (decoded is List) {
          return decoded.map((e) => e.toString()).toList();
        }
      } catch (e) {
        return [];
      }
    }
    if (value is List) {
      return value.map((e) => e.toString()).toList();
    }
    return [];
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'email': email,
      'first_name': firstName,
      'middle_name': middleName,
      'last_name': lastName,
      'suffix': suffix,
      'contact_number': contactNumber,
      'address': address,
      'birth_date': birthDate,
      'age': age,
      'sex': sex,
      'civil_status': civilStatus,
      'educational_status': educationalStatus,
      'employment_status': employmentStatus,
      'organization_name': organizationName,
      'youth_classification': youthClassification,
      'programs_interested': programsInterested,
      'created_at': createdAt,
    };
  }
}
