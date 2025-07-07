// assets/js/blockchain.js
// Requires: ethers v5, MetaMask

// Contract address will be updated after deployment
// You need to replace this with your actual deployed contract address
const CONTRACT_ADDRESS = '0x5fbdb2315678afecb367f032d93f642f64180aa3';
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
    try {
        if (!window.ethereum) throw new Error('MetaMask not found');
        console.log('MetaMask found, requesting accounts...');
        
        await window.ethereum.request({ method: 'eth_requestAccounts' });
        console.log('Accounts requested successfully');
        
        const provider = new ethers.providers.Web3Provider(window.ethereum);
        const signer = provider.getSigner();
        console.log('Provider and signer created successfully');
        
        return { provider, signer };
    } catch (error) {
        console.error('Error in connectMetaMask:', error);
        throw error;
    }
}

function hashString(str) {
    try {
        const hash = ethers.utils.keccak256(ethers.utils.toUtf8Bytes(str));
        console.log('Hash created for string:', str, 'Hash:', hash);
        return hash;
    } catch (error) {
        console.error('Error in hashString:', error);
        throw error;
    }
}

async function registerUserOnChain(email) {
    try {
        console.log('Starting registerUserOnChain for email:', email);
        const { signer } = await connectMetaMask();
        const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
        const userHash = hashString(email);
        let timeout = setTimeout(() => {
            alert('Transaction is taking longer than expected. Please check your MetaMask extension and confirm the transaction.');
        }, 15000);
        const tx = await contract.registerUser(userHash);
        clearTimeout(timeout);
        alert('User registration transaction sent to blockchain!\nHash: ' + userHash + '\nTxHash: ' + tx.hash);
        return { userHash, txHash: tx.hash };
    } catch (error) {
        throw error;
    }
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