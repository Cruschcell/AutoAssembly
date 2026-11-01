CREATE DATABASE autoassemblyDB;
USE autoassemblyDB;

-- Customer Table
CREATE TABLE customer (
    customerID INT AUTO_INCREMENT PRIMARY KEY,
    lname VARCHAR(50) NOT NULL,
    fname VARCHAR(50) NOT NULL,
    mname VARCHAR(50),
    customerPW VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(15) UNIQUE,
    phone VARCHAR(20) UNIQUE,
    profile_picture VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role VARCHAR(20) DEFAULT 'customer'
);

DELIMITER //
CREATE TRIGGER assign_admin_role
AFTER INSERT ON customer
FOR EACH ROW
BEGIN
    IF NEW.username = 'Administrator' THEN
        UPDATE customer
        SET role = 'admin'
        WHERE customerID = NEW.customerID;
    END IF;
END//
DELIMITER ;

UPDATE customer
SET role='admin'
WHERE username='administrator';

-- Warehouse Table 
CREATE TABLE warehouse (
    warehouseID INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(100) NOT NULL
);

-- Category Table
CREATE TABLE category (
    ctgID INT AUTO_INCREMENT PRIMARY KEY,
    ctgName VARCHAR(50) NOT NULL
);

CREATE TABLE category_img(
	ctgThumbnail VARCHAR(255),
    ctgOverlay VARCHAR(255),
	ctgID INT,
    FOREIGN KEY (ctgID) REFERENCES category(ctgID)
);
-- Brand Table
CREATE TABLE brand (
	brandID INT AUTO_INCREMENT PRIMARY KEY,
    brandName VARCHAR(50) NOT NULL
);

-- Product Table
CREATE TABLE product (
    productID INT AUTO_INCREMENT PRIMARY KEY,
    prodName VARCHAR(50) NOT NULL,
    prodDesc VARCHAR(250) NOT NULL,
    prodPrice DECIMAL(10,2) NOT NULL,
    prodInv INT DEFAULT 0,
    shipped_from INT NOT NULL,
    ctgID INT NOT NULL,
    brandID INT NOT NULL,
    thumbnail VARCHAR(255),
    FOREIGN KEY (shipped_from) REFERENCES warehouse(warehouseID),
    FOREIGN KEY (ctgID) REFERENCES category(ctgID),
    FOREIGN KEY (brandID) REFERENCES brand(brandID)
);
-- Product Image Table
CREATE TABLE prod_desc (
    descID INT AUTO_INCREMENT PRIMARY KEY,
    prodImg VARCHAR(255) NOT NULL,
    imgPos INT NOT NULL,
    prodDesc TEXT,
    specs TEXT,
    productID INT NOT NULL,
    FOREIGN KEY (productID) REFERENCES product(productID)
);

-- Cart Table
CREATE TABLE cart (
	cartID INT AUTO_INCREMENT PRIMARY KEY,
    customerID INT NOT NULL,
    productID INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (customerID) REFERENCES customer(customerID),
    FOREIGN KEY (productID) REFERENCES product(productID)
);

-- Wishlist Table
CREATE TABLE wishlist (
	wishlistID INT AUTO_INCREMENT PRIMARY KEY,
    customerID INT NOT NULL,
    productID INT NOT NULL,
    FOREIGN KEY (customerID) REFERENCES customer(customerID),
    FOREIGN KEY (productID) REFERENCES product(productID)
);

-- Orders Table
CREATE TABLE orders (
    orderID INT AUTO_INCREMENT PRIMARY KEY,
    customerID INT,
    orderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    orderStatus ENUM('to be delivered','delivered','failed') DEFAULT 'to be delivered',
    totalAmount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (customerID) REFERENCES customer(customerID)
);

-- Order Details Table
CREATE TABLE order_details (
    orderID INT NOT NULL,
    productID INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orderID) REFERENCES orders(orderID),
    FOREIGN KEY (productID) REFERENCES product(productID)
);

-- Payment Table
CREATE TABLE payment (
    paymentID INT AUTO_INCREMENT PRIMARY KEY,
    paymentType VARCHAR(50) NOT NULL,
    billAddress VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paymentStatus ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    orderID INT NOT NULL,
    FOREIGN KEY (orderID) REFERENCES orders(orderID)
);

-- Reviews Table
CREATE TABLE reviews (
    revID INT AUTO_INCREMENT PRIMARY KEY,
    revcontent VARCHAR(255),
    rating ENUM('1','2','3','4','5') NOT NULL,
    customerID INT NOT NULL,
    productID INT NOT NULL,
    revDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customerID) REFERENCES customer(customerID),
    FOREIGN KEY (productID) REFERENCES product(productID)
);


-- Viewed Products Table
CREATE table viewed_product (
	viewID INT AUTO_INCREMENT PRIMARY KEY,
    customerID INT,
    productID INT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(customerID) REFERENCES customer(customerID),
    FOREIGN KEY(productID) REFERENCES product(productID),
    UNIQUE KEY(customerID,productID)
);

-- Inventory Sync
DELIMITER //
CREATE TRIGGER orders_update_inventory_on_status_change
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- Only proceed if status changed
    IF NEW.orderStatus != OLD.orderStatus THEN
        -- Delivery completion (only process if coming from 'to be delivered')
        IF NEW.orderStatus = 'delivered' AND OLD.orderStatus = 'to be delivered' THEN
            UPDATE product p
            JOIN order_details od ON p.productID = od.productID
            SET p.prodInv = p.prodInv - od.quantity
            WHERE od.orderID = NEW.orderID;
        
        -- Order failure
        ELSEIF NEW.orderStatus = 'failed' THEN
            -- Coming from 'to be delivered'
            IF OLD.orderStatus = 'to be delivered' THEN
                UPDATE product p
                JOIN order_details od ON p.productID = od.productID
                SET p.prodInv = p.prodInv + od.quantity
                WHERE od.orderID = NEW.orderID;
            
            -- Coming from 'delivered' (returns)
            ELSEIF OLD.orderStatus = 'delivered' THEN
                UPDATE product p
                JOIN order_details od ON p.productID = od.productID
                SET p.prodInv = p.prodInv + od.quantity
                WHERE od.orderID = NEW.orderID;
            END IF;
        
        -- Reattempting delivery
        ELSEIF NEW.orderStatus = 'to be delivered' AND OLD.orderStatus = 'failed' THEN
            UPDATE product p
            JOIN order_details od ON p.productID = od.productID
            SET p.prodInv = p.prodInv - od.quantity
            WHERE od.orderID = NEW.orderID;
        END IF;
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER handle_payment_failure
AFTER UPDATE ON payment
FOR EACH ROW
BEGIN
    -- Only handle payment failures
    IF NEW.paymentStatus = 'failed' AND OLD.paymentStatus != 'failed' THEN
        UPDATE orders
        SET orderStatus = 'failed'
        WHERE orderID = NEW.orderID
        AND orderStatus = 'to be delivered';
    END IF;
END//
DELIMITER ;

CREATE TABLE friends(
	friendID INT AUTO_INCREMENT PRIMARY KEY,
    senderID INT NOT NULL,
    receiverID INT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY(senderID) REFERENCES customer(customerID),
    FOREIGN KEY(receiverID) REFERENCES customer(customerID),
    UNIQUE KEY (senderID,receiverID)
);
ALTER TABLE orders
ADD COLUMN giftStatus ENUM('pending', 'delivered', 'cancelled') DEFAULT NULL,
ADD COLUMN isGift BOOLEAN DEFAULT FALSE,
ADD COLUMN recipientID INT NULL,
ADD COLUMN mobileNumber VARCHAR(100) NULL,
ADD FOREIGN KEY (recipientID) REFERENCES customer(customerID);

CREATE TABLE wishlist_gifts (
    giftID INT AUTO_INCREMENT PRIMARY KEY,
    wishlistID INT NOT NULL,
    buyerID INT NOT NULL,
    recipientID INT,
    message TEXT,
    is_claimed BOOLEAN DEFAULT FALSE,
    claimed_at TIMESTAMP NULL,
    FOREIGN KEY (wishlistID) REFERENCES wishlist(wishlistID),
    FOREIGN KEY (buyerID) REFERENCES customer(customerID),
    FOREIGN KEY (recipientID) REFERENCES customer(customerID)
);

INSERT INTO warehouse
VALUES
-- Simplfied logistics
-- We now use Invetory Hubs or something
(1,'Laguna Technopark, Biñan'), -- Main Hub
(2,'PITX, Parañaque'), -- NCR Hub
(3,'Clark Freeport Zone, Pampanga'), -- North Luzon Hub
(4,'Mandaue Multipurpose Terminal, Cebu'), -- Visayas Regional Hub 
(5,'Buhangin Trade Center, Davao City'); -- Mindanao Regional Hub


INSERT INTO category
VALUES
(1,'Exterior'),
(2,'Lighting'),
(3,'Wheels and Tires'),
(4,'Interior'),
(5,'Performance'),
(6,'Tech and Electronics'),
(7,'Body Kits and Style'),
(8,'Suspension and Handling'),
(9,'Exhaust'),
(10,'Off Road'),
(11,'Car Care'),
(12,'Safety and Security');


INSERT INTO brand
VALUES
(1,'NexGen Performance'),
(2,'ApexLegends'),
(3,'Titan Shield'),
(4,'VelocitaRacing'),
(5,'StatTrak Moto'),
(6,'Chromex Styles'),
(7,'HeartsOfIron'),
(8,'Vortex Wheelz'),
(9,'NOS bltz'),
(10,'TrekkTerra Off-road'),
(11,'Hawk2Drift'),
(12,'Solaris Lighting'),
(13,'BoltsWise'),
(14,'DiddyOil'),
(15,'Eclipse Motors'),
(16,'Pulse Charger Inc.'),
(17,'Skylines Aerotech'),
(18,'StealthBuild'),
(19,'AutoBahnDeutchlan'),
(20,'Sandevistan Co.');

INSERT INTO product (prodName, prodDesc, prodPrice, prodInv, brandID, shipped_from, ctgID, thumbnail) 
VALUES
-- Exterior Accessories (ctgID = 1) - Mostly Cebu (4) for Visayas market
('Carbon Fiber Hood', 'Lightweight carbon fiber hood', 15000.00, 35, 4, 4, 1,'https://i.imgur.com/5kiBmi8.jpeg'),
('Carbon Fiber Rear Spoiler', 'Premium carbon fiber spoiler', 8500.00, 42, 6, 4, 1,'https://i.imgur.com/JUriTah.png'),
('Front Lip Splitter', 'Aerodynamic front lip splitter', 3200.00, 58, 18, 4, 1,'https://i.imgur.com/13mj22H.jpeg'),
('Chrome Door Handle Covers', 'Stylish chrome-plated covers', 1200.00, 120, 6, 4, 1,'https://i.imgur.com/y3UvCJd.jpeg'),
('Side Window Louvers', 'Custom fit window louvers', 2800.00, 85, 18, 4, 1,'https://i.imgur.com/uksY0nI.jpeg'),
('Hood Scoop', 'Performance hood scoop', 4500.00, 32, 17, 4, 1,'https://i.imgur.com/oYZM88n.png'),
('Roof Rack', 'Durable roof rack system', 7200.00, 45, 10, 4, 1,'https://i.imgur.com/kKcE4Fr.png'),
('Rear Diffuser', 'Aerodynamic rear diffuser', 5600.00, 38, 17, 4, 1,'https://i.imgur.com/XJXxjUc.png'),
('Custom Grille', 'Premium aftermarket grille', 4300.00, 52, 4, 4, 1,'https://i.imgur.com/YU00Ai9.jpeg'),
('Wind Deflectors', 'Side window deflectors', 1800.00, 95, 12, 4, 1,'https://i.imgur.com/7YJLiKv.png'),
('Fender Flares', 'Wide-body fender flares', 6500.00, 28, 3, 4, 1,'https://i.imgur.com/ZIeQGN0.png'),

-- Lighting (ctgID = 2) - NCR Hub (2) for fast tech turnover
('LED Headlight Bulbs', 'Bright LED conversion kit', 3200.00, 150, 12, 2, 2,'https://i.imgur.com/w2nmWYE.png'),
('RGB Underglow Kit', 'App-controlled RGB system', 2800.00, 65, 12, 2, 2,'https://i.imgur.com/s9oHpu3.jpeg'),
('Fog Lights', 'High-performance fog lights', 4500.00, 42, 15, 2, 2,'https://i.imgur.com/HRB49eh.jpeg'),
('Sequential Turn Signals', 'Dynamic LED signals', 1600.00, 87, 5, 2, 2,'https://i.imgur.com/GMYV7fk.jpeg'),
('LED Light Bar', 'Powerful light bar', 5800.00, 34, 12, 2, 2,'https://i.imgur.com/GpZOiRl.png'),
('Strobe Light Kit', 'Emergency strobe system', 2200.00, 48, 15, 2, 2,'https://i.imgur.com/LTcrWm1.jpeg'),
('Halo Projector Headlights', 'Angel eye headlights', 7600.00, 29, 15, 2, 2,'https://i.imgur.com/ToSZcC2.png'),
('License Plate LED Bulbs', 'Ultra-bright bulbs', 850.00, 210, 12, 2, 2,'https://i.imgur.com/YO0PyZU.png'),
('Glow-in-the-Dark Wheel Lights', 'Wheel well lighting', 1400.00, 72, 12, 2, 2,'https://i.imgur.com/e6Y26rn.png'),
('Tail Light Tinting Film', 'Pre-cut tinting film', 950.00, 128, 18, 2, 2,'https://i.imgur.com/U37JqXE.png'),

-- Wheels & Tires (ctgID = 3) - Clark (3) for bulky items
('Forged Alloy Wheels', 'Lightweight forged wheels', 32500.00, 16, 8, 3, 3,'https://i.imgur.com/1W7JdQt.png'),
('Off-Road Tires', 'All-terrain tires', 8800.00, 36, 10, 3, 3,'https://i.imgur.com/XCxxPjc.png'),
('Wheel Spacers', 'Precision spacers', 2200.00, 64, 7, 3, 3,'https://i.imgur.com/zUZUlzs.png'),
('Chrome Wheel Covers', 'Snap-on covers', 1850.00, 92, 6, 3, 3,'https://i.imgur.com/WOyZM6x.png'),
('Run-Flat Tire Kit', 'Run-flat tires', 12400.00, 24, 20, 3, 3,'https://i.imgur.com/1RPznXg.jpeg'),
('Steel Rims', 'Heavy duty rims', 4800.00, 38, 3, 3, 3,'https://i.imgur.com/dXTApfq.jpeg'),
('Tire Pressure Monitoring', 'Real-time monitoring', 3600.00, 47, 5, 3, 3,'https://i.imgur.com/jUPhzqZ.png'),
('Custom Wheel Lug Nuts', 'Locking lug nuts', 1200.00, 85, 8, 3, 3,'https://i.imgur.com/F7nbnvN.png'),
('All-Season Tires', 'Performance tires', 9200.00, 42, 8, 3, 3,'https://i.imgur.com/O1DdABb.jpeg'),
('Spare Tire Cover', 'Custom design cover', 1400.00, 76, 10, 3, 3,'https://i.imgur.com/E5cBXbd.png'),

-- Interior Accessories (ctgID = 4) - Laguna (1) main hub
('Leather Seat Covers', 'Premium leather covers', 15800.00, 22, 11, 1, 4,'https://i.imgur.com/k6pRqNf.jpeg'),
('Custom Floor Mats', 'All-weather mats', 4800.00, 56, 14, 1, 4,'https://i.imgur.com/bstfywR.jpeg'),
('Carbon Fiber Steering Wheel', 'Sport steering wheel', 11200.00, 18, 11, 1, 4,'https://i.imgur.com/uJZZYXc.jpeg'),
('Shift Knob', 'Weighted shift knob', 2600.00, 42, 11, 1, 4,'https://i.imgur.com/6W2ITho.jpeg'),
('Dashboard Cover', 'Anti-glare dash cover', 1800.00, 68, 13, 1, 4,'https://i.imgur.com/8Z3yTqg.jpeg'),
('Seatbelt Pads', 'Comfort pads', 850.00, 124, 13, 1, 4,'https://i.imgur.com/Q7rVhRV.jpeg'),
('Headrest Monitors', 'Entertainment displays', 9600.00, 24, 16, 1, 4,'https://i.imgur.com/lrlmhw1.jpeg'),
('Car Pillows', 'Neck support pillows', 1200.00, 86, 13, 1, 4,'https://i.imgur.com/p2xyVWl.jpeg'),
('Suction Cup Phone Mount', 'Dashboard mount', 1950.00, 72, 5, 1, 4,'https://i.imgur.com/LzTOTSc.jpeg'),
('Interior LED Strip Lights', 'Ambient lighting', 1400.00, 58, 12, 1, 4,'https://i.imgur.com/Hkwppc3.png'),

-- Performance Upgrades (ctgID = 5) - Laguna (1)
('Cold Air Intake', 'Performance intake', 3500.00, 48, 1, 1, 5,'https://i.imgur.com/vuRN1eZ.png'),
('Turbocharger Kit', 'Complete turbo system', 28500.00, 12, 1, 1, 5,'https://i.imgur.com/VGXaWfo.jpeg'),
('Performance Exhaust', 'Stainless exhaust', 12400.00, 22, 1, 1, 5,'https://i.imgur.com/2SireJb.jpeg'),
('ECU Tuning Chip', 'Performance chip', 4800.00, 36, 2, 1, 5,'https://i.imgur.com/RGPyE4S.png'),
('Lowering Springs', 'Sport springs', 3600.00, 42, 2, 1, 5,'https://i.imgur.com/IdM0OjX.jpeg'),
('High-Flow Catalytic', 'Performance converter', 5200.00, 28, 2, 1, 5,'https://i.imgur.com/rmCLf3D.png'),
('Blow-Off Valve', 'Turbo BOV', 2800.00, 54, 9, 1, 5,'https://i.imgur.com/t2w3iFu.png'),
('Intercooler Upgrade', 'High capacity', 8600.00, 18, 2, 1, 5,'https://i.imgur.com/PYg7xl9.png'),
('Lightweight Pulley Kit', 'Underdrive pulleys', 3200.00, 32, 19, 1, 5,'https://i.imgur.com/Elri3V7.jpeg'),
('Nitrous Oxide System', 'Complete nitrous', 12800.00, 14, 9, 1, 5,'https://i.imgur.com/GPPhAhb.jpeg'),

-- Tech & Electronics (ctgID = 6) - NCR (2)
('Touchscreen Head Unit', 'Multimedia receiver', 18500.00, 26, 5, 2, 6,'https://i.imgur.com/wkYVH0Z.png'),
('Wireless CarPlay', 'Wireless adapter', 4200.00, 48, 5, 2, 6,'https://i.imgur.com/zeSFa8K.jpeg'),
('Rearview Camera', 'HD backup camera', 3800.00, 54, 15, 2, 6,'https://i.imgur.com/MWpBV5U.jpeg'),
('GPS Tracker', 'Real-time tracking', 2400.00, 62, 5, 2, 6,'https://i.imgur.com/ffNpfFg.jpeg'),
('Dash Cam', 'Dual channel camera', 5600.00, 38, 5, 2, 6,'https://i.imgur.com/KUlZ6Ki.png'),
('Blind Spot Monitor', 'Radar detection', 4800.00, 42, 5, 2, 6,'https://i.imgur.com/xg9eSvK.png'),
('Tire Inflator', 'Portable compressor', 2200.00, 76, 16, 2, 6,'https://i.imgur.com/SkV34W9.jpeg'),
('Jump Starter', 'Lithium starter', 3600.00, 58, 16, 2, 6,'https://i.imgur.com/s4fw5Op.png'),
('OBD2 Scanner', 'Diagnostic scanner', 2800.00, 64, 5, 2, 6,'https://i.imgur.com/vixutoY.jpeg'),
('In-Car Wi-Fi', 'Mobile hotspot', 4200.00, 32, 16, 2, 6,'https://i.imgur.com/PQ82lqc.png'),

-- Body Kits (ctgID = 7) - Cebu (4)
('Widebody Kit', 'Complete widebody', 42000.00, 8, 17, 4, 7,'https://i.imgur.com/lDAs8A3.jpeg'),
('Vinyl Wrap', 'Color change wrap', 28000.00, 12, 18, 4, 7,'https://i.imgur.com/KAT1J0W.jpeg'),
('Chrome Delete Kit', 'Blackout vinyl', 3800.00, 46, 18, 4, 7,'https://i.imgur.com/DZHLK8I.png'),
('Carbon Fiber Hood', 'Replacement hood', 24500.00, 14, 17, 4, 7,'https://i.imgur.com/4C38SK8.png'),
('Rear Wing', 'Adjustable wing', 16800.00, 18, 4, 4, 7,'https://i.imgur.com/awAxjXE.jpeg'),
('Bumper Lip', 'Front lip spoiler', 5200.00, 38, 17, 4, 7,'https://i.imgur.com/2eCll64.jpeg'),
('Sticker Decals', 'Custom graphics', 4600.00, 52, 18, 4, 7,'https://i.imgur.com/7a1NNkq.jpeg'),
('Paint Protection Film', 'Transparent film', 15800.00, 16, 18, 4, 7,'https://i.imgur.com/fpFOXk5.jpeg'),
('Mirror Caps', 'Custom covers', 3600.00, 42, 18, 4, 7,'https://i.imgur.com/LeCWmxd.jpeg'),
('Roof Spoiler', 'Aerodynamic spoiler', 8200.00, 24, 17, 4, 7,'https://i.imgur.com/MaDpfgM.png'),

-- Suspension (ctgID = 8) - Clark (3)
('Coilover Kit', 'Adjustable system', 32000.00, 14, 3, 3, 8,'https://i.imgur.com/Lc1Bmk2.jpeg'),
('Sway Bars', 'Anti-roll bars', 8600.00, 32, 3, 3, 8,'https://i.imgur.com/VcTLl4t.png'),
('Air Suspension', 'Adjustable air ride', 46000.00, 8, 3, 3, 8,'https://i.imgur.com/ysookPp.png'),
('Strut Tower Brace', 'Chassis bracing', 5800.00, 36, 3, 3, 8,'https://i.imgur.com/1hrQF6f.jpeg'),
('Wheel Alignment Kit', 'Alignment tools', 7400.00, 22, 7, 3, 8,'https://i.imgur.com/G96QgJ1.png'),
('Performance Shocks', 'Shock absorbers', 12800.00, 28, 3, 3, 8,'https://i.imgur.com/0Wr2u1y.jpeg'),
('Traction Bars', 'Traction control', 6400.00, 26, 3, 3, 8,'https://i.imgur.com/rWvBEBy.jpeg'),
('Lower Control Arms', 'Adjustable arms', 9200.00, 18, 7, 3, 8,'https://i.imgur.com/fbn7oUe.jpeg'),
('Polyurethane Bushings', 'Bushing kit', 4800.00, 34, 3, 3, 8,'https://i.imgur.com/1PljnrA.png'),
('Hydraulic Handbrake', 'Drift handbrake', 16500.00, 12, 11, 3, 8,'https://i.imgur.com/YVQ7xXZ.jpeg'),

-- Exhaust (ctgID = 9) - Cebu (4) for marine-grade
('Cat-Back Exhaust', 'Stainless system', 18500.00, 22, 1, 4, 9,'https://i.imgur.com/ZJzMfYa.png'),
('Dual Exhaust Tip', 'Polished tips', 2800.00, 48, 1, 4, 9,'https://i.imgur.com/fb82HTL.jpeg'),
('Muffler Delete Pipe', 'Direct pipe', 3600.00, 36, 1, 4, 9,'https://i.imgur.com/irrERKq.png'),
('Resonator Delete Kit', 'Sound enhancement', 2400.00, 42, 1, 4, 9,'https://i.imgur.com/Uh5OdNQ.jpeg'),
('Stainless Steel Headers', 'High-flow headers', 14800.00, 18, 1, 4, 9,'https://i.imgur.com/LWmxqRs.jpeg'),

-- Off-Road (ctgID = 10) - Davao (5)
('Lift Kit', 'Suspension lift', 24500.00, 16, 10, 5, 10,'https://i.imgur.com/m1KRNaz.png'),
('Skid Plate', 'Undercarriage plate', 8600.00, 24, 10, 5, 10,'https://i.imgur.com/RwtF0vA.png'),
('Snorkel', 'Water crossing', 12400.00, 18, 10, 5, 10,'https://i.imgur.com/H0wY5DL.jpeg'),
('Winch', 'Recovery winch', 16500.00, 22, 10, 5, 10,'https://i.imgur.com/gWlSlza.png'),
('Bull Bar', 'Bumper protection', 9800.00, 28, 10, 5, 10,'https://i.imgur.com/8lk0GSQ.jpeg'),
('Rock Sliders', 'Rocker protection', 7600.00, 26, 7, 5, 10,'https://i.imgur.com/kSKNhot.png'),
('Off-Road Jack', 'High-lift jack', 4800.00, 34, 7, 5, 10,'https://i.imgur.com/BPYYD1V.png'),
('Mud Terrain Tires', 'Aggressive tires', 10200.00, 24, 10, 5, 10,'https://i.imgur.com/EQUquim.png'),
('Light Pods', 'LED light pods', 6200.00, 36, 12, 5, 10,'https://i.imgur.com/tbiyiZe.jpeg'),
('Recovery Straps', 'Tow straps', 2400.00, 48, 10, 5, 10,'https://i.imgur.com/mQ8kopL.png'),

-- Car Care (ctgID = 11) - Laguna (1)
('Ceramic Coating', 'Paint protection', 18500.00, 14, 14, 1, 11,'https://i.imgur.com/xFjCztM.jpeg'),
('Car Wax', 'Carnauba wax', 2800.00, 86, 14, 1, 11,'https://i.imgur.com/iVt02yq.jpeg'),
('Foam Cannon', 'Pressure washer', 3600.00, 42, 14, 1, 11,'https://i.imgur.com/7dQVcL0.jpeg'),
('Microfiber Towels', 'Detailing towels', 1400.00, 120, 14, 1, 11,'https://i.imgur.com/AyBdFF8.jpeg'),
('Clay Bar Kit', 'Paint decontamination', 2200.00, 68, 14, 1, 11,'https://i.imgur.com/QqnlAef.png'),

-- Safety (ctgID = 12) - NCR (2)
('Vortex Dash Cam Pro', 'HD dash camera', 3499.00, 120, 3, 2, 12,'https://i.imgur.com/N79xtrK.jpeg'),
('Titan Blind Spot Mirrors', 'Wide-angle mirrors', 399.00, 300, 3, 2, 12,'https://i.imgur.com/uJaqO5q.jpeg'),
('Apex Car Alarm X10', 'Anti-theft alarm', 5299.00, 80, 2, 2, 12,'https://i.imgur.com/FY8L4Il.jpeg'),
('Stealth Parking Sensor', 'Rear sensors', 1899.00, 150, 18, 2, 12,'https://i.imgur.com/JYRkJLI.png'),
('Titan Steering Wheel Lock', 'Anti-theft lock', 999.00, 200, 3, 2, 12,'https://i.imgur.com/qPPKD6W.jpeg');


INSERT INTO prod_desc (prodImg, imgPos, prodDesc, specs, productID)
VALUES
-- Exterior Accessories (ctgID = 1)
('https://i.imgur.com/Fzimk4C.jpeg', 1, 
'Premium lightweight carbon fiber hood designed to reduce weight and enhance engine cooling.
Provides an aggressive race-inspired look while improving performance.',
'Material: Aerospace-grade 2x2 twill weave carbon fiber. Finish: UV-protected high-gloss clear coat.
Weight: 60% lighter than OEM hood.
Fitment: Direct bolt-on with factory mounting points.
Installation: All hardware included, professional installation recommended.', 1),
('https://i.imgur.com/FpooTiM.jpeg', 2, NULL, NULL, 1),('https://i.imgur.com/oFzfSk2.jpeg', 3, NULL, NULL, 1),

('https://i.imgur.com/ucEYvrH.png', 1, 
'High-quality carbon fiber rear spoiler that improves downforce and stability at high speeds. 
Features a sleek design that enhances your vehicle''s aerodynamic profile',
'Material: 3K carbon fiber with UV-resistant clear coat. 
Dimensions: Vehicle specific (typically 52-58" width). 
Mounting: 3M automotive-grade adhesive tape and hardware included. 
Finish: High-gloss with exposed carbon weave pattern. 
Compatibility: Vehicle-specific designs available.', 2),
('https://i.imgur.com/eTQx4vi.png', 2, NULL, NULL, 2),('https://i.imgur.com/wxcpudL.png', 3, NULL, NULL, 2),

('https://i.imgur.com/6ydbVgn.jpeg', 1, 
'Aerodynamic front lip splitter that reduces front-end lift and improves handling.
Creates a lower, more aggressive stance while protecting your factory bumper.',
'Material: Durable ABS plastic with carbon fiber overlay. 
Thickness: 4mm reinforced construction. 
Hardware: Stainless steel screws and brackets included. 
Installation: Direct fit with pre-drilled mounting points. 
Flexibility: Slightly flexible to prevent cracking on impact.', 3),
('https://i.imgur.com/Q9AIrgb.jpeg', 2, NULL, NULL, 3),('https://i.imgur.com/up98Ujc.png', 3, NULL, NULL, 3),

('https://i.imgur.com/vNyrn2B.png', 1, 
'Chrome-plated door handle covers that add a touch of elegance to your vehicle''s exterior.
Easy peel-and-stick installation transforms dull factory handles instantly.',
'Material: Triple-chrome plated ABS plastic.
Fitment: Vehicle-specific designs for precise coverage.
Installation: 3M automotive-grade adhesive backing.
Package Contents: Complete set for all door handles.
Finish: Mirror-like high-gloss chrome appearance.', 4),
('https://i.imgur.com/Snu6Qnt.jpeg', 2, NULL, NULL, 4),('https://i.imgur.com/G7Ft2w6.png', 3, NULL, NULL, 4),

('https://i.imgur.com/VGE8Euu.png', 1, 
'Custom fit window louvers that add a sporty, aggressive look to your vehicle.
Helps reduce interior heat by blocking direct sunlight while maintaining visibility.',
'High-impact ABS plastic construction.
UV and weather resistant.
Includes installation hardware and adhesive strips.', 5),
('https://i.imgur.com/oxHWyGl.png', 2, NULL, NULL, 5),('https://i.imgur.com/zjVURjf.png', 3, NULL, NULL, 5),

('https://i.imgur.com/m4rffjz.png', 1, 
'',
'', 6),
('https://i.imgur.com/bWopr5u.jpeg', 2, NULL, NULL, 6),('https://i.imgur.com/8QjDEY6.jpeg', 3, NULL, NULL, 6),

('https://i.imgur.com/yFg0vo4.png', 1, 
'',
'', 7),
('https://i.imgur.com/W2i8ARO.jpeg', 2, NULL, NULL, 7),('https://i.imgur.com/9dhUQa0.png', 3, NULL, NULL, 7),

('https://i.imgur.com/UtIpOHO.png', 1, 
'',
'', 8),
('https://i.imgur.com/8nnSmjB.jpeg', 2, NULL, NULL, 8),('https://i.imgur.com/0I8ObCF.png', 3, NULL, NULL, 8),

('https://i.imgur.com/X2RTL1s.png', 1, 
'',
'', 9),
('https://i.imgur.com/8nnSmjB.jpeg', 2, NULL, NULL, 9),('https://i.imgur.com/dOqcyti.png', 3, NULL, NULL, 9),

('https://i.imgur.com/IE53NNK.png', 1, 
'',
'', 10),
('https://i.imgur.com/ujtaJ8f.png', 2, NULL, NULL, 10),('https://i.imgur.com/PHzt03q.png', 3, NULL, NULL, 10),

('https://i.imgur.com/Atq5Pbq.jpeg', 1, 
'',
'', 11),
('https://i.imgur.com/SwdX6gR.png', 2, NULL, NULL, 11),('https://i.imgur.com/0JPvmYB.png', 3, NULL, NULL, 11),
-- Lighting (ctgID = 2)
('https://i.imgur.com/qjylBNl.jpeg', 1, 
'',
'', 12),
('https://i.imgur.com/GhWrtMh.jpeg', 2, NULL, NULL, 12),('https://i.imgur.com/qr4hUlK.png', 3, NULL, NULL, 12),

('https://i.imgur.com/3tgWx0X.png', 1, 
'',
'', 13),
('https://i.imgur.com/jOxiQHw.png', 2, NULL, NULL, 13),('https://i.imgur.com/ByInVbB.png', 3, NULL, NULL, 13),

('https://i.imgur.com/Uob2xoL.png', 1, 
'',
'', 14),
('https://i.imgur.com/5K0wlok.png', 2, NULL, NULL, 14),('https://i.imgur.com/g9JG25M.png', 3, NULL, NULL, 14),

('https://i.imgur.com/Kmr0nav.jpeg', 1, 
'',
'', 15),
('https://i.imgur.com/coalBbN.png', 2, NULL, NULL, 15),('https://i.imgur.com/i9xfPE2.png', 3, NULL, NULL, 15),

('https://i.imgur.com/HftFQkK.png', 1, 
'',
'', 16),
('https://i.imgur.com/OU6RPcm.jpeg', 2, NULL, NULL, 16),('https://i.imgur.com/knyxReA.png', 3, NULL, NULL, 16),

('https://i.imgur.com/ROGr910.jpeg', 1, 
'',
'', 17),
('https://i.imgur.com/7s19tiX.jpeg', 2, NULL, NULL, 17),('https://i.imgur.com/REqxSAZ.png', 3, NULL, NULL, 17),

('https://i.imgur.com/xsXCorB.png', 1, 
'',
'', 18),
('https://i.imgur.com/MqNyVkj.png', 2, NULL, NULL, 18),('https://i.imgur.com/VkUMeUz.png', 3, NULL, NULL, 18),

('https://i.imgur.com/uKIHbBU.png', 1, 
'',
'', 19),
('https://i.imgur.com/pXvTyzY.png', 2, NULL, NULL, 19),('https://i.imgur.com/rv5Z5Pr.jpeg', 3, NULL, NULL, 19),

('https://i.imgur.com/Pb09baP.png', 1, 
'',
'', 20),
('https://i.imgur.com/Pe2TkhU.png', 2, NULL, NULL, 20),('https://i.imgur.com/UPPBTaQ.png', 3, NULL, NULL, 20),

('https://i.imgur.com/idI1gIz.jpeg', 1, 
'',
'', 21),
('https://i.imgur.com/ltv5NLu.png', 2, NULL, NULL, 21),('https://i.imgur.com/gavvBL6.png', 3, NULL, NULL, 21),
-- Wheels & Tires (ctgID = 3)
('https://i.imgur.com/uvo2hEb.jpeg', 1, 
'',
'', 22),
('https://i.imgur.com/90557jE.png', 2, NULL, NULL, 22),('https://i.imgur.com/WaN2x8E.png', 3, NULL, NULL, 22),

('https://i.imgur.com/Os0yriw.png', 1, 
'',
'', 23),
('https://i.imgur.com/D7UioTv.png', 2, NULL, NULL, 23),('https://i.imgur.com/57zw5Mx.png', 3, NULL, NULL, 23),

('https://i.imgur.com/GGgLqEG.jpeg', 1, 
'',
'', 24),
('https://i.imgur.com/dT8Y7xx.png', 2, NULL, NULL, 24),('https://i.imgur.com/KptlRm8.png', 3, NULL, NULL, 24),

('https://i.imgur.com/PIvRqCc.png', 1, 
'',
'', 25),
('https://i.imgur.com/lNurVDg.jpeg', 2, NULL, NULL, 25),('https://i.imgur.com/sK4Kn95.jpeg', 3, NULL, NULL, 25),

('https://i.imgur.com/etqa5Ol.png', 1, 
'',
'', 26),
('https://i.imgur.com/XveOTYc.png', 2, NULL, NULL, 26),('https://i.imgur.com/1RPznXg.jpeg', 3, NULL, NULL, 26),

('https://i.imgur.com/m2CrUs7.png', 1, 
'',
'', 27),
('https://i.imgur.com/GrCEKk5.png', 2, NULL, NULL, 27),('https://i.imgur.com/dXTApfq.jpeg', 3, NULL, NULL, 27),

('https://i.imgur.com/uVdTJL5.png', 1, 
'',
'', 28),
('https://i.imgur.com/sP1Ytol.png', 2, NULL, NULL, 28),('https://i.imgur.com/DKCfzXe.png', 3, NULL, NULL, 28),

('https://i.imgur.com/1XjD71z.png', 1, 
'',
'', 29),
('https://i.imgur.com/eaUCohA.png', 2, NULL, NULL, 29),('https://i.imgur.com/E5RLK9E.png', 3, NULL, NULL, 29),

('https://i.imgur.com/YfB0RO3.png', 1, 
'',
'', 30),
('https://i.imgur.com/mpsVynd.png', 2, NULL, NULL, 30),('https://i.imgur.com/9T9G5TW.jpeg', 3, NULL, NULL, 30),

('https://i.imgur.com/E5cBXbd.png', 1, 
'',
'', 31),
('https://i.imgur.com/sOFi5l5.png', 2, NULL, NULL, 31),('https://i.imgur.com/RxLQTe8.png', 3, NULL, NULL, 31),
-- Interior Accessories (ctgID = 4)
('https://i.imgur.com/PTVWYTx.jpeg', 1, 
'',
'', 32),
('https://i.imgur.com/HN0O07K.jpeg', 2, NULL, NULL, 32),('https://i.imgur.com/PpjTjWG.jpeg', 3, NULL, NULL, 32),

('https://i.imgur.com/wtEfvMU.jpeg', 1, 
'',
'', 33),
('https://i.imgur.com/fSZqd1Y.jpeg', 2, NULL, NULL, 33),('https://i.imgur.com/celaeRE.jpeg', 3, NULL, NULL, 33),

('https://i.imgur.com/baNadBL.jpeg', 1, 
'',
'', 34),
('https://i.imgur.com/HKtOirU.jpeg', 2, NULL, NULL, 34),('https://i.imgur.com/6A5sNWq.jpeg', 3, NULL, NULL, 34),

('https://i.imgur.com/CEgIybF.jpeg', 1, 
'',
'', 35),
('https://i.imgur.com/X9VY8Fn.jpeg', 2, NULL, NULL, 35),('https://i.imgur.com/GI21mPy.jpeg', 3, NULL, NULL, 35),

('https://i.imgur.com/CEgIybF.jpeg', 1, 
'',
'', 36),
('https://i.imgur.com/X9VY8Fn.jpeg', 2, NULL, NULL, 36),('https://i.imgur.com/GI21mPy.jpeg', 3, NULL, NULL, 36),

('https://i.imgur.com/kUr8r1S.jpeg', 1, 
'',
'', 37),
('https://i.imgur.com/YhYZLKF.jpeg', 2, NULL, NULL, 37),('https://i.imgur.com/UPJvkG5.jpeg', 3, NULL, NULL, 37),

('https://i.imgur.com/xfvXakl.jpeg', 1, 
'',
'', 38),
('https://i.imgur.com/fbSpahk.jpeg', 2, NULL, NULL, 38),('https://i.imgur.com/EePVbU1.jpeg', 3, NULL, NULL, 38),

('https://i.imgur.com/gzTv7HB.png', 1, 
'',
'', 39),
('https://i.imgur.com/5dJSl9T.png', 2, NULL, NULL, 39),('https://i.imgur.com/zR5dq1i.png', 3, NULL, NULL, 39),

('https://i.imgur.com/FGdcP01.jpeg', 1, 
'',
'', 40),
('https://i.imgur.com/2t2eZ1b.jpeg', 2, NULL, NULL, 40),('https://i.imgur.com/v50Kffh.jpeg', 3, NULL, NULL, 40),

('https://i.imgur.com/htcgKEy.jpeg', 1, 
'',
'', 41),
('https://i.imgur.com/Vep3UcH.jpeg', 2, NULL, NULL, 41),('https://i.imgur.com/f9EsGCd.jpeg', 3, NULL, NULL, 41),
-- Performance Upgrades (ctgID = 5)
('https://i.imgur.com/OFMV0iA.png', 1, 
'',
'', 42),
('https://i.imgur.com/kACN5Vd.png', 2, NULL, NULL, 42),('https://i.imgur.com/MHZoilq.png', 3, NULL, NULL, 42),

('https://i.imgur.com/hvlk4Us.png', 1, 
'',
'', 43),
('https://i.imgur.com/adlDIhO.png', 2, NULL, NULL, 43),('https://i.imgur.com/W71bIqZ.png', 3, NULL, NULL, 43),

('https://i.imgur.com/kpvT1Ac.jpeg', 1, 
'',
'', 44),
('https://i.imgur.com/wMFrBYB.jpeg', 2, NULL, NULL, 44),('https://i.imgur.com/7dDElzl.jpeg', 3, NULL, NULL, 44),

('https://i.imgur.com/0CAkyq9.png', 1, 
'',
'', 45),
('https://i.imgur.com/0ieo454.png', 2, NULL, NULL, 45),('https://i.imgur.com/2mVR0RF.png', 3, NULL, NULL, 45),

('https://i.imgur.com/sxE2d0X.jpeg', 1, 
'',
'', 46),
('https://i.imgur.com/ewQGroj.png', 2, NULL, NULL, 46),('https://i.imgur.com/gJp2c9w.png', 3, NULL, NULL, 46),

('https://i.imgur.com/ePlqteu.jpeg', 1, 
'',
'', 47),
('https://i.imgur.com/Zc11co7.png', 2, NULL, NULL, 47),('https://i.imgur.com/sR3T7Gr.png', 3, NULL, NULL, 47),

('https://i.imgur.com/xzfAOGm.png', 1, 
'',
'', 48),
('https://i.imgur.com/JHUismf.png', 2, NULL, NULL, 48),('https://i.imgur.com/rWSkWDB.png', 3, NULL, NULL, 48),

('https://i.imgur.com/f67upsM.jpeg', 1, 
'',
'', 49),
('https://i.imgur.com/BuSUVNZ.jpeg', 2, NULL, NULL, 49),('https://i.imgur.com/7jSHPPO.jpeg', 3, NULL, NULL, 49),

('https://i.imgur.com/Yo4tPz1.jpeg', 1, 
'',
'', 50),
('https://i.imgur.com/0Dfhb5Z.jpeg', 2, NULL, NULL, 50),('https://i.imgur.com/lWAoTXk.jpeg', 3, NULL, NULL, 50),

('https://i.imgur.com/GPPhAhb.jpeg', 1, 
'',
'', 51),
('https://i.imgur.com/En6OV0m.jpeg', 2, NULL, NULL, 51),('https://i.imgur.com/e08TjxT.jpeg', 3, NULL, NULL, 51),
-- Tech & Electronics (ctgID = 6)
('https://i.imgur.com/J0w6FWp.jpeg', 1, 
'',
'', 52),
('https://i.imgur.com/PtLSMX5.jpeg', 2, NULL, NULL, 52),('https://i.imgur.com/86rdfbA.jpeg', 3, NULL, NULL, 52),

('https://i.imgur.com/ZJ4ew2n.jpeg', 1, 
'',
'', 53),
('https://i.imgur.com/NmzQZHr.jpeg', 2, NULL, NULL, 53),('https://i.imgur.com/5fdoX6h.jpeg', 3, NULL, NULL, 53),

('https://i.imgur.com/gctVuLQ.jpeg', 1, 
'',
'', 54),
('https://i.imgur.com/4GONWE0.jpeg', 2, NULL, NULL, 54),('https://i.imgur.com/XlEJN7w.jpeg', 3, NULL, NULL, 54),

('https://i.imgur.com/tpP4AI3.jpeg', 1, 
'',
'', 55),
('https://i.imgur.com/ZmJOVlF.jpeg', 2, NULL, NULL, 55),('https://i.imgur.com/ytaf47v.jpeg', 3, NULL, NULL, 55),

('https://i.imgur.com/YBfDx99.png', 1, 
'',
'', 56),
('https://i.imgur.com/6zlOVeN.png', 2, NULL, NULL, 56),('https://i.imgur.com/7JRf4BY.png', 3, NULL, NULL, 56),

('https://i.imgur.com/aMLrufL.jpeg', 1, 
'',
'', 57),
('https://i.imgur.com/EJY4MLH.jpeg', 2, NULL, NULL, 57),('https://i.imgur.com/QakoQLZ.jpeg', 3, NULL, NULL, 57),

('https://i.imgur.com/ToSM3CA.jpeg', 1, 
'',
'', 58),
('https://i.imgur.com/SsaP4fK.jpeg', 2, NULL, NULL, 58),('https://i.imgur.com/KFbCpXB.jpeg', 3, NULL, NULL, 58),

('https://i.imgur.com/uuoprzz.png', 1, 
'',
'', 59),
('https://i.imgur.com/DUh2eYz.png', 2, NULL, NULL, 59),('https://i.imgur.com/xjFqG3n.png', 3, NULL, NULL, 59),

('https://i.imgur.com/4wBqFPO.png', 1, 
'',
'', 60),
('https://i.imgur.com/mGK40dE.png', 2, NULL, NULL, 60),('https://i.imgur.com/vixutoY.jpeg', 3, NULL, NULL, 60),

('https://i.imgur.com/rHfQeqb.png', 1, 
'',
'', 61),
('https://i.imgur.com/vYBNz7a.png', 2, NULL, NULL, 61),('https://i.imgur.com/PQ82lqc.png', 3, NULL, NULL, 61),
-- Body Kits & Styling (ctgID = 7)
('https://i.imgur.com/OuS4mi8.jpeg', 1, 
'',
'', 62),
('https://i.imgur.com/vMeUNNx.jpeg', 2, NULL, NULL, 62),('https://i.imgur.com/p7NoWYh.png', 3, NULL, NULL, 62),

('https://i.imgur.com/QzSn6xn.jpeg', 1, 
'',
'', 63),
('https://i.imgur.com/aZKbCSO.png', 2, NULL, NULL, 63),('https://i.imgur.com/Mztya5z.jpeg', 3, NULL, NULL, 63),

('https://i.imgur.com/3HeHTUj.png', 1, 
'',
'', 64),
('https://i.imgur.com/0Qo8I4E.png', 2, NULL, NULL, 64),('https://i.imgur.com/JZgN7jE.jpeg', 3, NULL, NULL, 64),

('https://i.imgur.com/i121L3o.jpeg', 1, 
'',
'', 65),
('https://i.imgur.com/mtE6M0h.jpeg', 2, NULL, NULL, 65),('https://i.imgur.com/cAsYWYR.jpeg', 3, NULL, NULL, 65),

('https://i.imgur.com/KaJROG0.jpeg', 1, 
'',
'', 66),
('https://i.imgur.com/MCiosEb.jpeg', 2, NULL, NULL, 66),('https://i.imgur.com/a9TiKi0.jpeg', 3, NULL, NULL, 66),

('https://i.imgur.com/ZCLzV0Q.jpeg', 1, 
'',
'', 67),
('https://i.imgur.com/FB0fBHh.jpeg', 2, NULL, NULL, 67),('https://i.imgur.com/EqOLQBs.jpeg', 3, NULL, NULL, 67),

('https://i.imgur.com/KWsGcMH.png', 1, 
'',
'', 68),
('https://i.imgur.com/fOcUZTz.jpeg', 2, NULL, NULL, 68),('https://i.imgur.com/jPw0mS3.png', 3, NULL, NULL, 68),

('https://i.imgur.com/2WkekCQ.png', 1, 
'',
'', 69),
('https://i.imgur.com/FXIPGB0.png', 2, NULL, NULL, 69),('https://i.imgur.com/79Z2QVs.jpeg', 3, NULL, NULL, 69),

('https://i.imgur.com/H3Lw0wr.jpeg', 1, 
'',
'', 70),
('https://i.imgur.com/xnVqxE5.png', 2, NULL, NULL, 70),('https://i.imgur.com/h4WXyx3.png', 3, NULL, NULL, 70),

('https://i.imgur.com/ZA2ebMU.png', 1, 
'',
'', 71),
('https://i.imgur.com/2IWLloF.png', 2, NULL, NULL, 71),('https://i.imgur.com/MzdVWVE.png', 3, NULL, NULL, 71),
-- Suspension & Handling (ctgID = 8)
('https://i.imgur.com/Vd7fvFv.jpeg', 1, 
'',
'', 72),
('https://i.imgur.com/62wAoWd.jpeg', 2, NULL, NULL, 72),('https://i.imgur.com/U3yHbKE.jpeg', 3, NULL, NULL, 72),

('https://i.imgur.com/DUkoSc6.jpeg', 1, 
'',
'', 73),
('https://i.imgur.com/LOHNypI.jpeg', 2, NULL, NULL, 73),('https://i.imgur.com/2ZHS58m.png', 3, NULL, NULL, 73),

('https://i.imgur.com/3Vd6qZn.png', 1, 
'',
'', 74),
('https://i.imgur.com/He3hf2d.png', 2, NULL, NULL, 74),('https://i.imgur.com/5SqfP4N.png', 3, NULL, NULL, 74),

('https://i.imgur.com/3lIe6Vl.jpeg', 1, 
'',
'', 75),
('https://i.imgur.com/69u2lHz.jpeg', 2, NULL, NULL, 75),('https://i.imgur.com/cFCTaIB.jpeg', 3, NULL, NULL, 75),

('https://i.imgur.com/jwBXpCd.png', 1, 
'',
'', 76),
('https://i.imgur.com/AFf9mZf.png', 2, NULL, NULL, 76),('https://i.imgur.com/i8Nr918.jpeg', 3, NULL, NULL, 76),

('https://i.imgur.com/qnMuyPQ.png', 1, 
'',
'', 77),
('https://i.imgur.com/v55B10I.png', 2, NULL, NULL, 77),('https://i.imgur.com/jRRMRmr.png', 3, NULL, NULL, 77),

('https://i.imgur.com/yats2yL.jpeg', 1, 
'',
'', 78),
('https://i.imgur.com/Ayao6US.jpeg', 2, NULL, NULL, 78),('https://i.imgur.com/GEmlkf4.jpeg', 3, NULL, NULL, 78),

('https://i.imgur.com/CAko3xC.png', 1, 
'',
'', 79),
('https://i.imgur.com/c6g79oH.png', 2, NULL, NULL, 79),('https://i.imgur.com/0gC5vIN.png', 3, NULL, NULL, 79),

('https://i.imgur.com/k35ryd1.jpeg', 1, 
'',
'', 80),
('https://i.imgur.com/LY34TCR.png', 2, NULL, NULL, 80),('https://i.imgur.com/D4mtLFN.jpeg', 3, NULL, NULL, 80),

('https://i.imgur.com/6lC2Fgy.jpeg', 1, 
'',
'', 81),
('https://i.imgur.com/xhu1SJQ.jpeg', 2, NULL, NULL, 81),('https://i.imgur.com/wFVAZHv.jpeg', 3, NULL, NULL, 81),
-- Exhaust Systems (ctgID = 9)
('https://i.imgur.com/xAoTKys.jpeg', 1, 
'',
'', 82),
('https://i.imgur.com/peGimSD.png', 2, NULL, NULL, 82),('https://i.imgur.com/M0Cm3iK.png', 3, NULL, NULL, 82),

('https://i.imgur.com/IBXP064.jpeg', 1, 
'',
'', 83),
('https://i.imgur.com/YU2D0yk.png', 2, NULL, NULL, 83),('https://i.imgur.com/GxXRNsc.jpeg', 3, NULL, NULL, 83),

('https://i.imgur.com/Lko5Jkf.png', 1, 
'',
'', 84),
('https://i.imgur.com/TY8ZvpF.png', 2, NULL, NULL, 84),('https://i.imgur.com/20kp6TO.png', 3, NULL, NULL, 84),

('https://i.imgur.com/Svk6V1U.png', 1, 
'',
'', 85),
('https://i.imgur.com/6nhdcWi.jpeg', 2, NULL, NULL, 85),('https://i.imgur.com/MATQ8h9.png', 3, NULL, NULL, 85),

('https://i.imgur.com/WEsiNcM.jpeg', 1, 
'',
'', 86),
('https://i.imgur.com/w40xmWz.png', 2, NULL, NULL, 86),('https://i.imgur.com/jMwU026.jpeg', 3, NULL, NULL, 86),
-- Off-Road Accessories (ctgID = 10)
('https://i.imgur.com/LiqQGGQ.png', 1, 
'',
'', 87),
('https://i.imgur.com/WPG8R5k.jpeg', 2, NULL, NULL, 87),('https://i.imgur.com/CiJqQcx.png', 3, NULL, NULL, 87),

('https://i.imgur.com/XhcKfi9.png', 1, 
'',
'', 88),
('https://i.imgur.com/mRKV9bM.png', 2, NULL, NULL, 88),('https://i.imgur.com/rcoJwxJ.png', 3, NULL, NULL, 88),

('https://i.imgur.com/FLgGuj6.jpeg', 1, 
'',
'', 89),
('https://i.imgur.com/BlEgNcf.jpeg', 2, NULL, NULL, 89),('https://i.imgur.com/IT8f3Rx.jpeg', 3, NULL, NULL, 89),

('https://i.imgur.com/VMJZeAo.jpeg', 1, 
'',
'', 90),
('https://i.imgur.com/j9b176q.png', 2, NULL, NULL, 90),('https://i.imgur.com/MU81XIy.png', 3, NULL, NULL, 90),

('https://i.imgur.com/VABCbMf.jpeg', 1, 
'',
'', 91),
('https://i.imgur.com/2pDUoDA.png', 2, NULL, NULL, 91),('https://i.imgur.com/2pDUoDA.png', 3, NULL, NULL, 91),

('https://i.imgur.com/M14mRDW.png', 1, 
'',
'', 92),
('https://i.imgur.com/y0SIfg0.jpeg', 2, NULL, NULL, 92),('https://i.imgur.com/EM3KltU.jpeg', 3, NULL, NULL, 92),

('https://i.imgur.com/wM9NfH0.png', 1, 
'',
'', 93),
('https://i.imgur.com/AOAmi6N.png', 2, NULL, NULL, 93),('https://i.imgur.com/umPthyA.png', 3, NULL, NULL, 93),

('https://i.imgur.com/W3wWEdd.png', 1, 
'',
'', 94),
('https://i.imgur.com/BMqlvpF.png', 2, NULL, NULL, 94),('https://i.imgur.com/GXz18MI.png', 3, NULL, NULL, 94),

('https://i.imgur.com/73PaI6u.jpeg', 1, 
'',
'', 95),
('https://i.imgur.com/LjEgVEZ.png', 2, NULL, NULL, 95),('https://i.imgur.com/grypAZY.jpeg', 3, NULL, NULL, 95),

('https://i.imgur.com/ZSYv6w5.jpeg', 1, 
'',
'', 96),
('https://i.imgur.com/hBF73yy.jpeg', 2, NULL, NULL, 96),('https://i.imgur.com/3Aynuf0.jpeg', 3, NULL, NULL, 96),
-- Car Care & Detailing (ctgID = 11)
('https://i.imgur.com/cSsRN4Z.png', 1, 
'',
'', 97),
('https://i.imgur.com/duByfqS.jpeg', 2, NULL, NULL, 97),('https://i.imgur.com/mqEtmAm.png', 3, NULL, NULL, 97),

('https://i.imgur.com/cSsRN4Z.png', 1, 
'',
'', 98),
('https://i.imgur.com/duByfqS.jpeg', 2, NULL, NULL, 98),('https://i.imgur.com/mqEtmAm.png', 3, NULL, NULL, 98),

('https://i.imgur.com/6aMIVn2.png', 1, 
'',
'', 99),
('https://i.imgur.com/XnNjQ1X.png', 2, NULL, NULL, 99),('https://i.imgur.com/I9RCnkM.jpeg', 3, NULL, NULL, 99),

('https://i.imgur.com/ZNu1qfG.jpeg', 1, 
'',
'', 100),
('https://i.imgur.com/0yHSVXu.png', 2, NULL, NULL, 100),('https://i.imgur.com/20tElxc.png', 3, NULL, NULL, 100),

('https://i.imgur.com/Bpd0Ckt.jpeg', 1, 
'',
'', 101),
('https://i.imgur.com/ggVk08d.jpeg', 2, NULL, NULL, 101),('https://i.imgur.com/qMoex3I.jpeg', 3, NULL, NULL, 101),
-- Safety and Security (CtgID = 12)
('https://i.imgur.com/Bpd0Ckt.jpeg', 1, 
'',
'', 102),
('https://i.imgur.com/ggVk08d.jpeg', 2, NULL, NULL, 102),('https://i.imgur.com/qMoex3I.jpeg', 3, NULL, NULL, 102),

('https://i.imgur.com/Bpd0Ckt.jpeg', 1, 
'',
'', 103),
('https://i.imgur.com/ggVk08d.jpeg', 2, NULL, NULL, 103),('https://i.imgur.com/qMoex3I.jpeg', 3, NULL, NULL, 103),

('https://i.imgur.com/j0bQxsI.jpeg', 1, 
'',
'', 104),
('https://i.imgur.com/h7hsPKd.jpeg', 2, NULL, NULL, 104),('https://i.imgur.com/aIkQJAv.jpeg', 3, NULL, NULL, 104),

('https://i.imgur.com/HFYeuqT.png', 1, 
'',
'', 105),
('https://i.imgur.com/IVNfVTL.png', 2, NULL, NULL, 105),('https://i.imgur.com/OjKbOwP.png', 3, NULL, NULL, 105),

('https://i.imgur.com/B4CMxlY.jpeg', 1, 
'',
'', 106),
('https://i.imgur.com/CLxptz3.jpeg', 2, NULL, NULL, 106),('https://i.imgur.com/t8XZ04I.jpeg', 3, NULL, NULL, 106)
;

INSERT INTO category_img
VALUES
('https://i.pinimg.com/736x/dc/43/c4/dc43c4909ca4e8b003af650566d0da35.jpg','https://c.pxhere.com/images/2a/da/23c18f7a3789213225472277680f-1457105.jpg!d',1),
('https://c.pxhere.com/photos/b7/eb/spotlight_light_blinker_auto_motor_vehicle_mitsubishi_lighting_lamp-1101740.jpg!d','https://c.pxhere.com/photos/b7/32/car_silver_sports_car_alloy_rims_light_alloy_rims_metallic_dark_modern-949391.jpg!d',2),
('https://c.pxhere.com/photos/45/72/rims_tires_chrome_wheel_auto_transportation_rubber_shiny-552020.jpg!d','https://c.pxhere.com/photos/87/b8/bmw_fast_speed_drift_car_tire_burn_smoke-675215.jpg!d',3),
('https://i.imgur.com/6ZHJJ9f.jpeg','https://c.pxhere.com/photos/82/b5/car_steering_wheel_steering_wheel_driving_vehicle_dashboard_interior-567808.jpg!d',4),
('https://c.pxhere.com/photos/7a/fc/vehicle_chrome_technology_automobile_motor_engine_compartment_supra_toyota-1012498.jpg!d','https://c.pxhere.com/images/b1/2b/99565779380b2fa6b049f422ccd3-1433821.jpg!d',5),
('https://c.pxhere.com/photos/52/5a/2001_wallpaper_ford_car_automobile_technology_tech_display-396112.jpg!d','https://c.pxhere.com/photos/24/c9/photo-1621264.jpg!d',6),
('https://c.pxhere.com/images/e2/ed/6d33c89da21e66ca5dc12c53316a-1451393.jpg!d','https://c.pxhere.com/images/c8/e5/951bc101f9be1c71b1de51d3dfe1-1448573.jpg!d',7),
('https://c.pxhere.com/photos/2e/07/yamaha_motorcycle_details_technology_chrome_metallic_metal_bikes-496616.jpg!d','https://c.pxhere.com/images/de/8a/cce908eb1b86bf6dc37cbff078b7-1445141.jpg!d',8),
('https://c.pxhere.com/photos/70/6e/exhaust_pipe_amg_benz_speed_power_more_power_sport_car-496561.jpg!d','https://c.pxhere.com/photos/95/42/sports_night_canon_drag_fire_eos_cool_action-533761.jpg!d',9),
('https://c.pxhere.com/photos/23/45/jeep_4x4_offroad_sky_cloud-1281622.jpg!d','https://c.pxhere.com/photos/f4/3a/photo-1416687.jpg!d',10),
('https://c.pxhere.com/photos/2a/d3/auto_washing_car_wax_piana_car_wash-1331033.jpg!d','https://c.pxhere.com/photos/0f/ba/cleaning_steam_smoke_pressure_pressure_washing_water_wash_clean-1176743.jpg!d',11),
('https://c.pxhere.com/images/a5/0a/b39b3d62a45d6e059667581c88b5-1637842.jpg!d','https://c.pxhere.com/photos/05/cc/driving_drive_road_trip_car_road-173204.jpg!d',12);

DROP DATABASE autoassemblyDB;
