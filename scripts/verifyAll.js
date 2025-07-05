const { ethers } = require("hardhat");
const CONTRACT_ADDRESS = "0x5fbdb2315678afecb367f032d93f642f64180aa3";

async function main() {
    const contract = await ethers.getContractAt("PharmaRegistry", CONTRACT_ADDRESS);

    const userEvents = await contract.queryFilter("UserRegistered");
    const pharmacyEvents = await contract.queryFilter("PharmacyRegistered");
    const medicineEvents = await contract.queryFilter("MedicineRegistered");
    const prescriptionEvents = await contract.queryFilter("PrescriptionUploaded");

    console.log("Users:", userEvents.map(e => ({
        user: e.args.user,
        userHash: e.args.userHash,
        timestamp: e.args.timestamp,
        txHash: e.transactionHash
    })));

    console.log("Pharmacies:", pharmacyEvents.map(e => ({
        user: e.args.user,
        pharmacyHash: e.args.pharmacyHash,
        timestamp: e.args.timestamp,
        txHash: e.transactionHash
    })));

    console.log("Medicines:", medicineEvents.map(e => ({
        user: e.args.user,
        medicineHash: e.args.medicineHash,
        timestamp: e.args.timestamp,
        txHash: e.transactionHash
    })));

    console.log("Prescriptions:", prescriptionEvents.map(e => ({
        user: e.args.user,
        prescriptionHash: e.args.prescriptionHash,
        timestamp: e.args.timestamp,
        txHash: e.transactionHash
    })));
}
main(); 