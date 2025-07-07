const { ethers } = require("hardhat");
const fs = require('fs');

async function main() {
    // Read contract address from file
    let CONTRACT_ADDRESS;
    try {
        CONTRACT_ADDRESS = fs.readFileSync('deployed_address.txt', 'utf8').trim();
        console.log("Using contract address:", CONTRACT_ADDRESS);
    } catch (error) {
        console.error("Error reading deployed_address.txt. Please make sure you have deployed the contract first.");
        console.error("Run: npx hardhat run scripts/deploy.js --network localhost");
        return;
    }

    try {
    const contract = await ethers.getContractAt("PharmaRegistry", CONTRACT_ADDRESS);

        console.log("\n=== BLOCKCHAIN VERIFICATION REPORT ===\n");

    const userEvents = await contract.queryFilter("UserRegistered");
    const pharmacyEvents = await contract.queryFilter("PharmacyRegistered");
    const medicineEvents = await contract.queryFilter("MedicineRegistered");
    const prescriptionEvents = await contract.queryFilter("PrescriptionUploaded");

        console.log(`📋 USERS REGISTERED (${userEvents.length}):`);
        if (userEvents.length === 0) {
            console.log("   No users registered yet.");
        } else {
            userEvents.forEach((e, index) => {
                console.log(`   ${index + 1}. User: ${e.args.user}`);
                console.log(`      Hash: ${e.args.userHash}`);
                console.log(`      Timestamp: ${new Date(e.args.timestamp * 1000).toLocaleString()}`);
                console.log(`      TX Hash: ${e.transactionHash}`);
                console.log("");
            });
        }

        console.log(`🏥 PHARMACIES REGISTERED (${pharmacyEvents.length}):`);
        if (pharmacyEvents.length === 0) {
            console.log("   No pharmacies registered yet.");
        } else {
            pharmacyEvents.forEach((e, index) => {
                console.log(`   ${index + 1}. User: ${e.args.user}`);
                console.log(`      Hash: ${e.args.pharmacyHash}`);
                console.log(`      Timestamp: ${new Date(e.args.timestamp * 1000).toLocaleString()}`);
                console.log(`      TX Hash: ${e.transactionHash}`);
                console.log("");
            });
        }

        console.log(`💊 MEDICINES REGISTERED (${medicineEvents.length}):`);
        if (medicineEvents.length === 0) {
            console.log("   No medicines registered yet.");
        } else {
            medicineEvents.forEach((e, index) => {
                console.log(`   ${index + 1}. User: ${e.args.user}`);
                console.log(`      Hash: ${e.args.medicineHash}`);
                console.log(`      Timestamp: ${new Date(e.args.timestamp * 1000).toLocaleString()}`);
                console.log(`      TX Hash: ${e.transactionHash}`);
                console.log("");
            });
        }

        console.log(`📄 PRESCRIPTIONS UPLOADED (${prescriptionEvents.length}):`);
        if (prescriptionEvents.length === 0) {
            console.log("   No prescriptions uploaded yet.");
        } else {
            prescriptionEvents.forEach((e, index) => {
                console.log(`   ${index + 1}. User: ${e.args.user}`);
                console.log(`      Hash: ${e.args.prescriptionHash}`);
                console.log(`      Timestamp: ${new Date(e.args.timestamp * 1000).toLocaleString()}`);
                console.log(`      TX Hash: ${e.transactionHash}`);
                console.log("");
            });
        }

        console.log("=== END OF REPORT ===");
        console.log("\n💡 TIP: If you see empty results, make sure:");
        console.log("   1. You have deployed the contract (npx hardhat run scripts/deploy.js --network localhost)");
        console.log("   2. You have registered users/pharmacies/medicines through the web app");
        console.log("   3. MetaMask is connected to the correct network (localhost:8545)");

    } catch (error) {
        console.error("Error connecting to contract:", error.message);
        console.error("Make sure the contract is deployed and the address is correct.");
    }
}

main().catch(console.error); 