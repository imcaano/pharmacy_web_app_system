import 'package:flutter/material.dart';
import 'lib/services/api_service.dart';

class TestPrescriptionWidget extends StatefulWidget {
  const TestPrescriptionWidget({super.key});

  @override
  State<TestPrescriptionWidget> createState() => _TestPrescriptionWidgetState();
}

class _TestPrescriptionWidgetState extends State<TestPrescriptionWidget> {
  List<Map<String, dynamic>> _testResults = [];
  bool _isLoading = false;

  Future<void> _testGetPrescriptions() async {
    setState(() {
      _isLoading = true;
      _testResults.add({
        'test': 'GET Prescriptions',
        'status': 'Running...',
        'timestamp': DateTime.now(),
      });
    });

    try {
      final prescriptions = await ApiService.fetchPrescriptions();
      setState(() {
        _testResults.last['status'] =
            '✅ PASS - Found ${prescriptions.length} prescriptions';
        _testResults.last['details'] =
            'Successfully fetched prescriptions from API';
      });
    } catch (e) {
      setState(() {
        _testResults.last['status'] = '❌ FAIL';
        _testResults.last['details'] = 'Error: ${e.toString()}';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _testAcceptPrescription() async {
    setState(() {
      _isLoading = true;
      _testResults.add({
        'test': 'Accept Prescription',
        'status': 'Running...',
        'timestamp': DateTime.now(),
      });
    });

    try {
      final response = await ApiService.acceptPrescription(1, 'accept');
      setState(() {
        _testResults.last['status'] = '✅ PASS';
        _testResults.last['details'] =
            'Successfully accepted prescription: ${response['message']}';
      });
    } catch (e) {
      setState(() {
        _testResults.last['status'] = '❌ FAIL';
        _testResults.last['details'] = 'Error: ${e.toString()}';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _testRejectPrescription() async {
    setState(() {
      _isLoading = true;
      _testResults.add({
        'test': 'Reject Prescription',
        'status': 'Running...',
        'timestamp': DateTime.now(),
      });
    });

    try {
      final response = await ApiService.acceptPrescription(
        2,
        'reject',
        notes: 'Test rejection from Flutter',
      );
      setState(() {
        _testResults.last['status'] = '✅ PASS';
        _testResults.last['details'] =
            'Successfully rejected prescription: ${response['message']}';
      });
    } catch (e) {
      setState(() {
        _testResults.last['status'] = '❌ FAIL';
        _testResults.last['details'] = 'Error: ${e.toString()}';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _runAllTests() async {
    await _testGetPrescriptions();
    await Future.delayed(const Duration(seconds: 1));
    await _testAcceptPrescription();
    await Future.delayed(const Duration(seconds: 1));
    await _testRejectPrescription();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('🧪 Test Prescription API'),
        backgroundColor: const Color(0xFF0b6e6e),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Prescription Acceptance Testing',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 20),

            // Test Buttons
            Row(
              children: [
                ElevatedButton(
                  onPressed: _isLoading ? null : _runAllTests,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                  ),
                  child: const Text(
                    '🚀 Run All Tests',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
                const SizedBox(width: 10),
                ElevatedButton(
                  onPressed: _isLoading ? null : _testGetPrescriptions,
                  child: const Text('📋 Get Prescriptions'),
                ),
                const SizedBox(width: 10),
                ElevatedButton(
                  onPressed: _isLoading ? null : _testAcceptPrescription,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                  ),
                  child: const Text(
                    '✅ Accept Test',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
                const SizedBox(width: 10),
                ElevatedButton(
                  onPressed: _isLoading ? null : _testRejectPrescription,
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                  child: const Text(
                    '❌ Reject Test',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 20),

            if (_isLoading)
              const Center(
                child: Column(
                  children: [
                    CircularProgressIndicator(),
                    SizedBox(height: 10),
                    Text('Running tests...'),
                  ],
                ),
              ),

            const SizedBox(height: 20),

            // Test Results
            const Text(
              'Test Results:',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 10),

            Expanded(
              child: ListView.builder(
                itemCount: _testResults.length,
                itemBuilder: (context, index) {
                  final result = _testResults[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(
                                result['test'],
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const Spacer(),
                              Text(
                                result['status'],
                                style: TextStyle(
                                  color: result['status'].contains('PASS')
                                      ? Colors.green
                                      : Colors.red,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                          if (result['details'] != null) ...[
                            const SizedBox(height: 5),
                            Text(
                              result['details'],
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 12,
                              ),
                            ),
                          ],
                          const SizedBox(height: 5),
                          Text(
                            'Time: ${result['timestamp'].toString().substring(11, 19)}',
                            style: TextStyle(
                              color: Colors.grey[500],
                              fontSize: 10,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
