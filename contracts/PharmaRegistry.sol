// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract PharmaRegistry {
    event UserRegistered(address indexed user, bytes32 userHash, uint256 timestamp);
    event PharmacyRegistered(address indexed user, bytes32 pharmacyHash, uint256 timestamp);
    event MedicineRegistered(address indexed user, bytes32 medicineHash, uint256 timestamp);
    event PrescriptionUploaded(address indexed user, bytes32 prescriptionHash, uint256 timestamp);

    mapping(bytes32 => bool) public userHashes;
    mapping(bytes32 => bool) public pharmacyHashes;
    mapping(bytes32 => bool) public medicineHashes;
    mapping(bytes32 => bool) public prescriptionHashes;

    function registerUser(bytes32 userHash) public {
        require(!userHashes[userHash], "User already registered");
        userHashes[userHash] = true;
        emit UserRegistered(msg.sender, userHash, block.timestamp);
    }

    function registerPharmacy(bytes32 pharmacyHash) public {
        require(!pharmacyHashes[pharmacyHash], "Pharmacy already registered");
        pharmacyHashes[pharmacyHash] = true;
        emit PharmacyRegistered(msg.sender, pharmacyHash, block.timestamp);
    }

    function registerMedicine(bytes32 medicineHash) public {
        require(!medicineHashes[medicineHash], "Medicine already registered");
        medicineHashes[medicineHash] = true;
        emit MedicineRegistered(msg.sender, medicineHash, block.timestamp);
    }

    function uploadPrescription(bytes32 prescriptionHash) public {
        require(!prescriptionHashes[prescriptionHash], "Prescription already uploaded");
        prescriptionHashes[prescriptionHash] = true;
        emit PrescriptionUploaded(msg.sender, prescriptionHash, block.timestamp);
    }

    // Verification functions
    function isUserRegistered(bytes32 userHash) public view returns (bool) {
        return userHashes[userHash];
    }
    function isPharmacyRegistered(bytes32 pharmacyHash) public view returns (bool) {
        return pharmacyHashes[pharmacyHash];
    }
    function isMedicineRegistered(bytes32 medicineHash) public view returns (bool) {
        return medicineHashes[medicineHash];
    }
    function isPrescriptionUploaded(bytes32 prescriptionHash) public view returns (bool) {
        return prescriptionHashes[prescriptionHash];
    }
} 