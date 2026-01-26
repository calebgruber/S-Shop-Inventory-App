-- Sample Data for Testing

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
    ('Microphones', 'All types of microphones'),
    ('Speakers', 'Speakers and monitors'),
    ('Cables', 'Audio cables and adapters'),
    ('Processors', 'Audio processors and effects'),
    ('Wireless', 'Wireless systems and receivers');

-- Insert sample theatre spaces
INSERT INTO theatre_spaces (name, description) VALUES 
    ('Main Stage', 'Primary performance space'),
    ('Black Box', 'Flexible performance space'),
    ('Studio Theatre', 'Intimate performance space'),
    ('Rehearsal Room A', 'Large rehearsal space'),
    ('Rehearsal Room B', 'Small rehearsal space');

-- Insert sample items
INSERT INTO items (name, description, barcode, category_id, tracking_type, total_quantity, in_stock_quantity) VALUES 
    ('Shure SM58', 'Dynamic vocal microphone', 'ITEM-MIC001', 1, 'quantity', 20, 20),
    ('Sennheiser EW 112P G4', 'Wireless lavalier system', 'ITEM-WL001', 5, 'quantity', 10, 10),
    ('QSC K12.2', '12" powered speaker', 'ITEM-SPK001', 2, 'quantity', 8, 8),
    ('XLR Cable 25ft', '25 foot XLR cable', 'ITEM-CBL001', 3, 'quantity', 50, 50),
    ('Shure SM57', 'Dynamic instrument microphone', 'ITEM-MIC002', 1, 'quantity', 15, 15),
    ('DI Box', 'Active direct box', 'ITEM-DI001', 4, 'quantity', 12, 12),
    ('Mic Stand', 'Boom microphone stand', 'ITEM-STD001', 1, 'quantity', 25, 25),
    ('Shure Beta 87A', 'Condenser vocal microphone', 'ITEM-MIC003', 1, 'quantity', 8, 8),
    ('Countryman B3', 'Lavalier microphone', 'ITEM-MIC004', 1, 'quantity', 12, 12),
    ('XLR Cable 50ft', '50 foot XLR cable', 'ITEM-CBL002', 3, 'quantity', 30, 30);

-- Insert a sample show
INSERT INTO shows (name, shop_lead, designer, theatre_space_id, status) VALUES 
    ('Hamlet', 'John Smith', 'Jane Doe', 1, 'active'),
    ('The Tempest', 'Alice Johnson', 'Bob Williams', 2, 'active'),
    ('Macbeth', 'Charlie Brown', 'Diana Prince', 1, 'completed');

