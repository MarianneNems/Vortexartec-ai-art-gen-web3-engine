// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/token/common/ERC2981.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/utils/Counters.sol";

/**
 * @title VortexArtwork
 * @dev ERC721 NFT contract for VORTEX AI Engine generated artworks
 * @dev Implements EIP-2981 for royalty enforcement
 * @dev Supports primary 5% royalty and configurable secondary royalties up to 15%
 */
contract VortexArtwork is ERC721, ERC721URIStorage, ERC2981, Ownable, ReentrancyGuard {
    using Counters for Counters.Counter;
    
    // Events
    event ArtworkMinted(uint256 indexed tokenId, address indexed to, string tokenURI);
    event RoyaltySet(uint256 indexed tokenId, address indexed receiver, uint96 feeNumerator);
    event MinterUpdated(address indexed oldMinter, address indexed newMinter);
    
    // State variables
    Counters.Counter private _tokenIdCounter;
    mapping(address => bool) public minters;
    mapping(uint256 => address) public artworkCreators;
    mapping(uint256 => uint96) public tokenRoyalties;
    
    // Constants
    uint96 public constant DEFAULT_ROYALTY_FEE = 500; // 5% = 500 basis points
    uint96 public constant MAX_SECONDARY_ROYALTY = 1500; // 15% = 1500 basis points
    
    // Modifiers
    modifier onlyMinter() {
        require(minters[msg.sender], "VortexArtwork: caller is not a minter");
        _;
    }
    
    modifier onlyTokenOwner(uint256 tokenId) {
        require(ownerOf(tokenId) == msg.sender, "VortexArtwork: caller is not token owner");
        _;
    }
    
    /**
     * @dev Constructor
     * @param name_ The name of the NFT collection
     * @param symbol_ The symbol of the NFT collection
     * @param defaultRoyaltyReceiver The default royalty receiver address
     * @param defaultRoyaltyFeeNumerator The default royalty fee (in basis points)
     */
    constructor(
        string memory name_,
        string memory symbol_,
        address defaultRoyaltyReceiver,
        uint96 defaultRoyaltyFeeNumerator
    ) ERC721(name_, symbol_) {
        require(defaultRoyaltyReceiver != address(0), "VortexArtwork: invalid royalty receiver");
        require(defaultRoyaltyFeeNumerator <= 10000, "VortexArtwork: royalty fee exceeds maximum");
        
        _setDefaultRoyalty(defaultRoyaltyReceiver, defaultRoyaltyFeeNumerator);
        minters[msg.sender] = true;
    }
    
    /**
     * @dev Mint a new artwork NFT
     * @param to The address to mint the NFT to
     * @param tokenURI The metadata URI for the NFT
     * @return tokenId The ID of the newly minted token
     */
    function mintArtwork(address to, string calldata tokenURI) 
        external 
        onlyMinter 
        nonReentrant 
        returns (uint256) 
    {
        require(to != address(0), "VortexArtwork: mint to zero address");
        require(bytes(tokenURI).length > 0, "VortexArtwork: empty token URI");
        
        uint256 tokenId = _tokenIdCounter.current();
        _tokenIdCounter.increment();
        
        _safeMint(to, tokenId);
        _setTokenURI(tokenId, tokenURI);
        
        // Store creator information
        artworkCreators[tokenId] = to;
        
        emit ArtworkMinted(tokenId, to, tokenURI);
        
        return tokenId;
    }
    
    /**
     * @dev Set royalty information for a specific token
     * @param tokenId The token ID to set royalty for
     * @param receiver The address that will receive royalty payments
     * @param feeNumerator The royalty fee in basis points (e.g., 500 = 5%)
     */
    function setTokenRoyalty(uint256 tokenId, address receiver, uint96 feeNumerator) 
        external 
        onlyTokenOwner(tokenId) 
    {
        require(_exists(tokenId), "VortexArtwork: token does not exist");
        require(receiver != address(0), "VortexArtwork: invalid receiver");
        require(feeNumerator <= MAX_SECONDARY_ROYALTY, "VortexArtwork: fee exceeds maximum");
        
        _setTokenRoyalty(tokenId, receiver, feeNumerator);
        tokenRoyalties[tokenId] = feeNumerator;
        
        emit RoyaltySet(tokenId, receiver, feeNumerator);
    }
    
    /**
     * @dev Add a new minter address
     * @param minter The address to add as a minter
     */
    function addMinter(address minter) external onlyOwner {
        require(minter != address(0), "VortexArtwork: invalid minter address");
        require(!minters[minter], "VortexArtwork: minter already exists");
        
        minters[minter] = true;
        emit MinterUpdated(address(0), minter);
    }
    
    /**
     * @dev Remove a minter address
     * @param minter The address to remove as a minter
     */
    function removeMinter(address minter) external onlyOwner {
        require(minters[minter], "VortexArtwork: minter does not exist");
        
        minters[minter] = false;
        emit MinterUpdated(minter, address(0));
    }
    
    /**
     * @dev Get the current token ID counter
     * @return The current token ID counter
     */
    function getCurrentTokenId() external view returns (uint256) {
        return _tokenIdCounter.current();
    }
    
    /**
     * @dev Get artwork creator address
     * @param tokenId The token ID
     * @return The creator address
     */
    function getArtworkCreator(uint256 tokenId) external view returns (address) {
        require(_exists(tokenId), "VortexArtwork: token does not exist");
        return artworkCreators[tokenId];
    }
    
    /**
     * @dev Get token royalty information
     * @param tokenId The token ID
     * @return receiver The royalty receiver address
     * @return royaltyAmount The royalty amount for the given sale price
     */
    function getTokenRoyalty(uint256 tokenId, uint256 salePrice) 
        external 
        view 
        returns (address receiver, uint256 royaltyAmount) 
    {
        require(_exists(tokenId), "VortexArtwork: token does not exist");
        return royaltyInfo(tokenId, salePrice);
    }
    
    /**
     * @dev Update default royalty settings (owner only)
     * @param receiver The new default royalty receiver
     * @param feeNumerator The new default royalty fee
     */
    function updateDefaultRoyalty(address receiver, uint96 feeNumerator) external onlyOwner {
        require(receiver != address(0), "VortexArtwork: invalid receiver");
        require(feeNumerator <= 10000, "VortexArtwork: fee exceeds maximum");
        
        _setDefaultRoyalty(receiver, feeNumerator);
    }
    
    /**
     * @dev Emergency function to delete default royalty (owner only)
     */
    function deleteDefaultRoyalty() external onlyOwner {
        _deleteDefaultRoyalty();
    }
    
    // Override required by Solidity for multiple inheritance
    function _burn(uint256 tokenId) internal override(ERC721, ERC721URIStorage) {
        super._burn(tokenId);
        _resetTokenRoyalty(tokenId);
        delete artworkCreators[tokenId];
        delete tokenRoyalties[tokenId];
    }
    
    function tokenURI(uint256 tokenId) public view override(ERC721, ERC721URIStorage) returns (string memory) {
        return super.tokenURI(tokenId);
    }
    
    function supportsInterface(bytes4 interfaceId) public view override(ERC721, ERC721URIStorage, ERC2981) returns (bool) {
        return super.supportsInterface(interfaceId);
    }
} 