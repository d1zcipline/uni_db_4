CREATE DATABASE IF NOT EXISTS moscow_transport;
USE moscow_transport;

-- Создание таблицы Districts
CREATE TABLE Districts (
    id_district INT PRIMARY KEY,
    district_name VARCHAR(50) NOT NULL
);

-- Создание таблицы Locations
CREATE TABLE Locations (
    id_location INT PRIMARY KEY,
    id_district INT,
    address VARCHAR(100),
    FOREIGN KEY (id_district) REFERENCES Districts(id_district)
);

-- Создание таблицы Bus_parks
CREATE TABLE Bus_parks (
    id_bus_park INT PRIMARY KEY,
    id_location INT,
    bus_park_name VARCHAR(30) NOT NULL,
    capacity INT,
    FOREIGN KEY (id_location) REFERENCES Locations(id_location)
);

-- Создание таблицы Employee_positions
CREATE TABLE Employee_positions (
    id_position INT PRIMARY KEY,
    position_name VARCHAR(50) NOT NULL
);

-- Создание таблицы Employees
CREATE TABLE Employees (
    id_employee INT PRIMARY KEY,
    id_bus_park INT,
    id_position INT,
    first_name VARCHAR(20) NOT NULL,
    middle_name VARCHAR(40),
    last_name VARCHAR(30),
    phone VARCHAR(20),
    email VARCHAR(100),
    active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_bus_park) REFERENCES Bus_parks(id_bus_park),
    FOREIGN KEY (id_position) REFERENCES Employee_positions(id_position)
);

-- Создание таблицы Work_shift_types
CREATE TABLE Work_shift_types (
    id_work_shift_type INT PRIMARY KEY,
    shift_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_duration INT
);

-- Создание таблицы Work_shifts
CREATE TABLE Work_shifts (
    id_work_shift INT PRIMARY KEY,
    id_work_shift_type INT,
    id_employee INT,
    shift_date DATE,
    FOREIGN KEY (id_work_shift_type) REFERENCES Work_shift_types(id_work_shift_type),
    FOREIGN KEY (id_employee) REFERENCES Employees(id_employee)
);

-- Создание таблицы Bus_types
CREATE TABLE Bus_types (
    id_bus_type INT PRIMARY KEY,
    bus_type_name VARCHAR(50) NOT NULL,
    electric BOOLEAN
);

-- Создание таблицы Statuses
CREATE TABLE Statuses (
    id_status INT PRIMARY KEY,
    status_name VARCHAR(30) NOT NULL
);

-- Создание таблицы Buses
CREATE TABLE Buses (
    id_bus INT PRIMARY KEY,
    id_bus_type INT,
    id_status INT,
    id_bus_park INT,
    license_plate VARCHAR(9),
    capacity INT,
    manufacture_year INT,
    last_maintenance_date DATE,
    FOREIGN KEY (id_bus_type) REFERENCES Bus_types(id_bus_type),
    FOREIGN KEY (id_status) REFERENCES Statuses(id_status),
    FOREIGN KEY (id_bus_park) REFERENCES Bus_parks(id_bus_park)
);

-- Создание таблицы Routes
CREATE TABLE Routes (
    id_route INT PRIMARY KEY,
    id_bus_park INT,
    route_name VARCHAR(100) NOT NULL,
    route_number VARCHAR(10),
    route_type VARCHAR(20),
    start_point VARCHAR(10),
    end_point VARCHAR(10),
    distance DECIMAL(10, 2),
    active BOOLEAN,
    FOREIGN KEY (id_bus_park) REFERENCES Bus_parks(id_bus_park)
);

-- Создание таблицы Schedule
CREATE TABLE Schedule (
    id_schedule INT PRIMARY KEY,
    id_route INT,
    day_type VARCHAR(20),
    departure_time TIME,
    arrival_time TIME,
    active BOOLEAN,
    FOREIGN KEY (id_route) REFERENCES Routes(id_route)
);

-- Создание таблицы Stops
CREATE TABLE Stops (
    id_stop INT PRIMARY KEY,
    stop_name VARCHAR(30) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8)
);

-- Создание таблицы Routes_stops
CREATE TABLE Routes_stops (
    id_route_stop INT PRIMARY KEY,
    id_route INT,
    id_stop INT,
    stop_order INT,
    FOREIGN KEY (id_route) REFERENCES Routes(id_route),
    FOREIGN KEY (id_stop) REFERENCES Stops(id_stop)
);

-- Создание таблицы Maintenance_type
CREATE TABLE Maintenance_type (
    id_maintenance_type INT PRIMARY KEY,
    maintenance_type_name VARCHAR(50) NOT NULL
);

-- Создание таблицы Maintenance_statuses
CREATE TABLE Maintenance_statuses (
    id_maintenance_status INT PRIMARY KEY,
    maintenance_status VARCHAR(20) NOT NULL
);

-- Создание таблицы Maintenance_records
CREATE TABLE Maintenance_records (
    id_maintenance INT PRIMARY KEY,
    id_bus INT,
    id_maintenance_type INT,
    id_maintenance_status INT,
    maintenance_date DATE,
    completion_date DATE,
    description TEXT,
    FOREIGN KEY (id_bus) REFERENCES Buses(id_bus),
    FOREIGN KEY (id_maintenance_type) REFERENCES Maintenance_type(id_maintenance_type),
    FOREIGN KEY (id_maintenance_status) REFERENCES Maintenance_statuses(id_maintenance_status)
);

-- Таблица пользователей
CREATE TABLE Users (
    id_user INT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'dispatcher', 'driver') NOT NULL DEFAULT 'driver',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица сессий
CREATE TABLE Sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id_user)
);