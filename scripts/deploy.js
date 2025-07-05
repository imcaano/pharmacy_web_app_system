const { ethers } = require("hardhat");
const fs = require('fs');

async function main() {
  const PharmaRegistry = await ethers.getContractFactory("PharmaRegistry");
  const contract = await PharmaRegistry.deploy();
  await contract.waitForDeployment();
  console.log("PharmaRegistry deployed to:", contract.target);
  fs.writeFileSync('deployed_address.txt', contract.target);
}

main().catch(console.error); 