-- ------------------------------
-- Table: users
-- ------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  role ENUM('admin','user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Table: parties
-- ------------------------------
CREATE TABLE IF NOT EXISTS parties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  email VARCHAR(100),
  address TEXT,
  profile_image VARCHAR(255), -- ✅ Added for party profile photo
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Table: items
-- ------------------------------
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ------------------------------
-- stock_adjustments
-- ------------------------------
CREATE TABLE IF NOT EXISTS stock_adjustments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  adjustment DECIMAL(12,2) NOT NULL, -- positive for add, negative for remove
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- price per unit for this adjustment
  note VARCHAR(255) DEFAULT NULL, -- optional note/reason
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Table: transactions
-- ------------------------------
CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  party_id INT NOT NULL,
  type ENUM('sale', 'purchase', 'income', 'expense', 'payment', 'receipt') NOT NULL,
  payment_mode ENUM('cash', 'bank', 'credit') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT,
  note TEXT,
  date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -- ------------------------------
-- -- Table: transaction_items
-- -- ------------------------------
CREATE TABLE IF NOT EXISTS transaction_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  qty DECIMAL(10,2) NOT NULL,
  rate DECIMAL(12,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Table: party_documents
-- ------------------------------
CREATE TABLE IF NOT EXISTS party_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  party_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  custom_name VARCHAR(255),
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Table: party_profile_links
-- ------------------------------
CREATE TABLE IF NOT EXISTS party_profile_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  party_id INT NOT NULL,
  label VARCHAR(100),
  url VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
