// assets/js/blockchain.js
// Requires: ethers v6, MetaMask

const CONTRACT_ADDRESS = '0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512';
const CONTRACT_ABI = [
  {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"user","type":"address"},{"indexed":false,"internalType":"bytes32","name":"medicineHash","type":"bytes32"},{"indexed":false,"internalType":"uint256","name":"timestamp","type":"uint256"}],"name":"MedicineRegistered","type":"event"},
  {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"user","type":"address"},{"indexed":false,"internalType":"bytes32","name":"pharmacyHash","type":"bytes32"},{"indexed":false,"internalType":"uint256","name":"timestamp","type":"uint256"}],"name":"PharmacyRegistered","type":"event"},
  {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"user","type":"address"},{"indexed":false,"internalType":"bytes32","name":"prescriptionHash","type":"bytes32"},{"indexed":false,"internalType":"uint256","name":"timestamp","type":"uint256"}],"name":"PrescriptionUploaded","type":"event"},
  {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"user","type":"address"},{"indexed":false,"internalType":"bytes32","name":"userHash","type":"bytes32"},{"indexed":false,"internalType":"uint256","name":"timestamp","type":"uint256"}],"name":"UserRegistered","type":"event"},
  {"inputs":[{"internalType":"bytes32","name":"medicineHash","type":"bytes32"}],"name":"isMedicineRegistered","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"pharmacyHash","type":"bytes32"}],"name":"isPharmacyRegistered","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"prescriptionHash","type":"bytes32"}],"name":"isPrescriptionUploaded","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"userHash","type":"bytes32"}],"name":"isUserRegistered","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"name":"medicineHashes","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"name":"pharmacyHashes","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"name":"prescriptionHashes","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"medicineHash","type":"bytes32"}],"name":"registerMedicine","outputs":[],"stateMutability":"nonpayable","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"pharmacyHash","type":"bytes32"}],"name":"registerPharmacy","outputs":[],"stateMutability":"nonpayable","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"userHash","type":"bytes32"}],"name":"registerUser","outputs":[],"stateMutability":"nonpayable","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"prescriptionHash","type":"bytes32"}],"name":"uploadPrescription","outputs":[],"stateMutability":"nonpayable","type":"function"},
  {"inputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"name":"userHashes","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"}
];

async function connectMetaMask() {
    if (!window.ethereum) throw new Error('MetaMask not found');
    await window.ethereum.request({ method: 'eth_requestAccounts' });
    const provider = new ethers.BrowserProvider(window.ethereum);
    const signer = await provider.getSigner();
    return { provider, signer };
}

function hashString(str) {
    return ethers.keccak256(ethers.toUtf8Bytes(str));
}

async function registerUserOnChain(email) {
    const { signer } = await connectMetaMask();
    const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
    const userHash = hashString(email);
    const tx = await contract.registerUser(userHash);
    await tx.wait();
    return { userHash, txHash: tx.hash };
}

async function registerPharmacyOnChain(pharmacyName) {
    const { signer } = await connectMetaMask();
    const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
    const pharmacyHash = hashString(pharmacyName);
    const tx = await contract.registerPharmacy(pharmacyHash);
    await tx.wait();
    return { pharmacyHash, txHash: tx.hash };
}

async function registerMedicineOnChain(medicineName) {
    const { signer } = await connectMetaMask();
    const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
    const medicineHash = hashString(medicineName);
    const tx = await contract.registerMedicine(medicineHash);
    await tx.wait();
    return { medicineHash, txHash: tx.hash };
}

async function uploadPrescriptionOnChain(prescriptionContent) {
    const { signer } = await connectMetaMask();
    const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
    const prescriptionHash = hashString(prescriptionContent);
    const tx = await contract.uploadPrescription(prescriptionHash);
    await tx.wait();
    return { prescriptionHash, txHash: tx.hash };
}

// Expose functions globally
window.registerUserOnChain = registerUserOnChain;
window.registerPharmacyOnChain = registerPharmacyOnChain;
window.registerMedicineOnChain = registerMedicineOnChain;
window.uploadPrescriptionOnChain = uploadPrescriptionOnChain;
window.hashString = hashString; 