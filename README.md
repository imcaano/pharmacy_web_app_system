# Pharmacy Web Application System

A modern web-based pharmacy management system with blockchain integration for prescription security.

## Features

- Multi-role user system (Admin, Pharmacy, Customer)
- Medicine inventory management
- Prescription upload and verification
- Order management system
- Blockchain integration for secure transactions
- Modern UI with responsive design
- Secure authentication system

## Technology Stack

- Backend: PHP
- Database: MySQL
- Frontend: HTML5, CSS3, JavaScript
- Blockchain: MetaMask Integration, Hardhat, Ethers.js
- Icons: Font Awesome
- CSS Framework: Bootstrap 5

## Setup Instructions

1. Clone the repository
2. Import the database schema from `database/pharmacy_db.sql`
3. Configure database connection in `config/database.php`
4. Start your local server (XAMPP/WAMP)
5. Access the application through your web browser

## Directory Structure

```
pharmacy_web_app_system/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── database/
├── includes/
├── admin/
├── pharmacy/
├── customer/
└── index.php
```

## Security Features

- Secure password hashing
- Blockchain-based prescription verification
- MetaMask integration for secure payments
- Role-based access control 

# Quickstart Guide

This guide explains how to run your full project (PHP/MySQL + Hardhat blockchain) on your local machine using XAMPP and Node.js/Hardhat.

### 1. Start XAMPP (Apache & MySQL)
- Open **XAMPP Control Panel**.
- Start **Apache** (for PHP) and **MySQL** (for your database).
- Make sure your database (`pharmacy_db.sql`) is imported into MySQL (use phpMyAdmin or the command line).

### 2. Install Node.js Dependencies
Open a terminal in your project folder (`C:/xampp/htdocs/pharmacy_web_app_system`) and run:
```sh
npm install
```

### 3. Compile the Smart Contract
In the same terminal, run:
```sh
npx hardhat compile
```

### 4. Start the Hardhat Local Blockchain
In the same terminal, run:
```sh
npx hardhat node
```
Leave this terminal open!  
This starts a local Ethereum blockchain at `http://127.0.0.1:8545/`.

### 5. Deploy the Smart Contract
Open a **new terminal** (keep the node running in the first one) and run:
```sh
npx hardhat run scripts/deploy.js --network localhost
```
- Copy the contract address from the output (or check `deployed_address.txt` if you used the script update).

### 6. Update Frontend with Contract Address & ABI
- Open `assets/js/blockchain.js`.
- Paste the contract address and ABI (already done if you followed previous steps).

### 7. Import a Hardhat Account into MetaMask
- In the terminal running `npx hardhat node`, you'll see a list of accounts and private keys.
- Open MetaMask > Import Account > Paste one of the private keys.
- Switch MetaMask to the "Localhost 8545" network.

### 8. Start Your PHP Web App
- Open your browser and go to:  
  `http://localhost/pharmacy_web_app_system/`
- You can now use your app as normal.

### 9. Use Blockchain Features
- When registering users, pharmacies, medicines, or uploading prescriptions, the frontend JS will interact with MetaMask and the blockchain.
- Transaction hashes and data hashes should be saved in MySQL as described.

### 10. Verify Blockchain Data
- To see all blockchain events, run:
  ```sh
  npx hardhat run scripts/verifyAll.js --network localhost
  ```
- Compare these with your MySQL data for verification.

# Blockchain Integration Guide

## 1. MetaMask & Ethers.js Setup
- Make sure MetaMask is installed in your browser.
- Import a Hardhat account private key for local testing (see Hardhat node output for keys).
- The frontend uses ethers v6 and MetaMask for all blockchain interactions.

## 2. assets/js/blockchain.js
- This file provides functions to:
  - Connect to MetaMask
  - Hash data (email, pharmacy name, medicine name, prescription content)
  - Call the smart contract for user, pharmacy, medicine, and prescription actions
  - Return the hash and transaction hash for saving in MySQL
- **You must set your contract address and ABI in this file.**

## 3. How to Use in PHP Forms
- On form submit (user, pharmacy, medicine, prescription):
  1. Call the relevant JS function (e.g., `registerUserOnChain(email)`).
  2. On success, send the returned hash and txHash to PHP (via AJAX or hidden form fields).
  3. Save these in MySQL for future verification.

**Example:**
```php
// In your PHP save logic
$userHash = $_POST['userHash'];
$txHash = $_POST['txHash'];
// Save $userHash and $txHash in your users table
```

## 4. Verification Script
- Use `scripts/verifyAll.js` to fetch and print all blockchain events for admin/audit.
- Run with:
  ```sh
  npx hardhat run scripts/verifyAll.js --network localhost
  ```
- Compare the hashes/txHashes in MySQL with those on-chain for verification.

## 5. Placeholders
- Replace `PASTE_YOUR_DEPLOYED_CONTRACT_ADDRESS_HERE` and `/* ... ABI from artifacts ... */` with your actual contract address and ABI.

## 6. Support
- If you need to add more entities, follow the same pattern: hash the data, call the contract, save the hash/txHash in MySQL, and verify with a script.

# PharmaRegistry Smart Contract Integration

## 1. Deploying the Smart Contract Locally (Hardhat)

1. Install Node.js and npm if not already installed.
2. Install Hardhat:
   ```sh
   npm install --save-dev hardhat
   ```
3. Initialize Hardhat in your project directory:
   ```sh
   npx hardhat
   # Choose 'Create a basic sample project'
   ```
4. Copy `contracts/PharmaRegistry.sol` into the `contracts/` folder.
5. Compile the contract:
   ```sh
   npx hardhat compile
   ```
6. Start a local Hardhat node:
   ```sh
   npx hardhat node
   ```
7. Deploy the contract (in a new terminal):
   ```sh
   npx hardhat run scripts/deploy.js --network localhost
   ```
   - Example `scripts/deploy.js`:
     ```js
     const { ethers } = require("hardhat");
     async function main() {
       const PharmaRegistry = await ethers.getContractFactory("PharmaRegistry");
       const contract = await PharmaRegistry.deploy();
       await contract.deployed();
       console.log("PharmaRegistry deployed to:", contract.address);
     }
     main();
     ```

## 2. Viewing Transactions and Data

- Use MetaMask to connect to the local Hardhat network (see Hardhat node output for accounts/private keys).
- Use [Hardhat's built-in block explorer](https://hardhat.org/hardhat-network-helpers/docs/reference#hardhat-network) or [BlockScout](https://docs.blockscout.com/) for advanced viewing.
- All contract events (UserRegistered, PharmacyRegistered, etc.) are visible in the Hardhat node logs and can be queried via scripts or UI.
- You can use `npx hardhat console --network localhost` to interact with the contract directly.
- **To view transactions in a block:**
  1. Create or update `scripts/showBlock.js` with the script in section 7 below.
  2. Run:
     ```sh
     npx hardhat run scripts/showBlock.js --network localhost
     ```
  3. Change `blockNumber` in the script to inspect other blocks.

## 3. Integrating with the Web App

- Use `ethers.js` or `web3.js` in your frontend (see `assets/js/blockchain.js` for example usage).
- For every registration or upload action, compute a hash (e.g., `keccak256(email)` for users, `keccak256(file)` for prescriptions) and call the contract method via MetaMask.
- Store the hash in your MySQL database for reference.

## 4. Example: Registering a User (JS)

```js
const provider = new ethers.providers.Web3Provider(window.ethereum);
const signer = provider.getSigner();
const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
const userHash = ethers.utils.keccak256(ethers.utils.toUtf8Bytes(email));
const tx = await contract.registerUser(userHash);
await tx.wait();
```

## 5. Viewing All Events

- All events are emitted and visible in the local node logs.
- You can also fetch all past events using `ethers.js`:

```js
const events = await contract.queryFilter('UserRegistered');
console.log(events);
```

## 6. Security

- All data is public on the blockchain, but only hashes are stored (no sensitive data).
- Use the blockchain as a verification layer alongside your MySQL database.

## 7. Final, Advanced Script: View All Transactions in a Block

Here is a robust script that will work regardless of Hardhat's `ethers` version and will show all transactions in a given block:

```js
const { ethers } = require("hardhat");
const { utils } = require("ethers"); // Use ethers.js utils directly

async function main() {
  const blockNumber = 1; // Change this to inspect other blocks
  const block = await ethers.provider.getBlock(blockNumber);
  if (!block) {
    console.log(`Block ${blockNumber} not found.`);
    return;
  }
  if (!block.transactions.length) {
    console.log(`Block ${blockNumber} has no transactions.`);
    return;
  }
  console.log(`Block ${blockNumber} transactions:`);
  for (const txHash of block.transactions) {
    const tx = await ethers.provider.getTransaction(txHash);
    if (!tx) continue;
    console.log(`- Hash: ${tx.hash}`);
    console.log(`  From: ${tx.from}`);
    console.log(`  To:   ${tx.to}`); // Note: 'to' is null for contract creation
    console.log(`  Value: ${utils.formatEther(tx.value)} ETH`);
    console.log("---");
  }
}

main().catch(console.error);
```

## 8. Troubleshooting

- **If you see `TypeError: Cannot read properties of undefined (reading 'formatEther')`:**
  - Make sure you are importing `utils` from the standalone `ethers` package, not from Hardhat's `ethers`.
  - Use: `const { utils } = require("ethers");`
- **If `To: null` in a transaction:**
  - This is normal for contract creation transactions (deployments).
- **If you see no transactions in a block:**
  - That block may not contain any transactions. Try interacting with your contract or sending ETH between accounts to generate more transactions.
- **If you get errors about Node.js version:**
  - Use Node.js v20.x or v18.x (LTS). Hardhat does not support v21.x or v22.x.

## 9. What to Do Next

1. Replace your `scripts/showBlock.js` with the script above if you want to view block transactions.
2. Run:
   ```sh
   npx hardhat run scripts/showBlock.js --network localhost
   ```
3. If you want to see other blocks, change `const blockNumber = 1;` to another block number.

## How to Get Your Deployed Contract Address

After deploying your contract with Hardhat, you need the contract address to interact with it from your frontend and scripts.

### 1. From Deployment Output
- When you run:
  ```sh
  npx hardhat run scripts/deploy.js --network localhost
  ```
- You will see output like:
  ```
  PharmaRegistry deployed to: 0x5fbdb2315678afecb367f032d93f642f64180aa3
  ```
- **Copy this address!** This is your deployed contract address.

### 2. Save the Address Automatically
- You can update your `scripts/deploy.js` to save the address to a file for easy access:
  ```js
  const fs = require('fs');
  async function main() {
    const PharmaRegistry = await ethers.getContractFactory("PharmaRegistry");
    const contract = await PharmaRegistry.deploy();
    await contract.deployed();
    console.log("PharmaRegistry deployed to:", contract.address);
    fs.writeFileSync('deployed_address.txt', contract.address);
  }
  main().catch(console.error);
  ```
- After running the script, check the `deployed_address.txt` file for your contract address.

### 3. Why You Need the Address
- The contract address is required in your frontend JS (`assets/js/blockchain.js`) and in scripts like `scripts/verifyAll.js` to interact with the deployed contract. 

# Troubleshooting
- Make sure MetaMask is on the right network and using a Hardhat account.
- Make sure your contract address and ABI are correct in the JS.
- Check the README for more details and troubleshooting tips.

**Enjoy your secure, blockchain-powered pharmacy system!** 