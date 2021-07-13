IF DB_ID('dbconnector') IS NULL
BEGIN
    CREATE DATABASE dbconnector;
    CREATE LOGIN dbconnector WITH PASSWORD = 'dbc0nnector@COB';
    CREATE USER dbconnector FOR LOGIN dbconnector;
    EXEC sp_addrolemember N'db_owner', N'dbconnector';
END

USE dbconnector;

IF NOT (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='users'))
BEGIN
    CREATE TABLE dbo.users (
        ID int IDENTITY(1,1) NOT NULL
            PRIMARY KEY,
        Username NVARCHAR(100) NOT NULL,
        Password NVARCHAR(150) NOT NULL,
        IPAddresses NVARCHAR(500),
        Role int DEFAULT 0,
        Enabled bit DEFAULT 1
    );
    CREATE UNIQUE INDEX UK_Username
        ON dbo.users (Username);
    INSERT INTO dbo.users (Username, Password, IPAddresses, Role, Enabled)
    VALUES
        ('david.upton@boston.gov', 'wV1/g/3LN3gZXmxhSNImkw==$0nM+7jTxyR7DR2sGs5UJrswFtVpNscYt2eAmeKylAVYFGrpO2fvVhnz6Tsz4EkEhRAVPK7sQgTHe7x90HumE0w==', '', 4096, 1),
        ('davidrkupton@gmail.com', 'jzY/3Zw/SLH/nH4fBPmfQQ==$Nu1CuTApDRtM2vy/ipAKy0Xpe/evQOAVdObEoRAI00Hi6YJlY4vHu+KoHgrEldEhh5Fo/+UXr+o09ANMbKyb8Q==', '', 2048, 1),
        ('havocint@hotmail.com', 'wV1/g/3LN3gZXmxhSNImkw==$0nM+7jTxyR7DR2sGs5UJrswFtVpNscYt2eAmeKylAVYFGrpO2fvVhnz6Tsz4EkEhRAVPK7sQgTHe7x90HumE0w==', '', 4, 1),
        ('david', 'wV1/g/3LN3gZXmxhSNImkw==$0nM+7jTxyR7DR2sGs5UJrswFtVpNscYt2eAmeKylAVYFGrpO2fvVhnz6Tsz4EkEhRAVPK7sQgTHe7x90HumE0w==', '10.10.10.10', 1, 1);
END

IF NOT (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='connTokens'))
BEGIN
    CREATE TABLE dbo.connTokens (
        ID int IDENTITY(1,1) NOT NULL
            PRIMARY KEY,
        Token UNIQUEIDENTIFIER ROWGUIDCOL NOT NULL
            DEFAULT NEWID(),
        ConnectionString NVARCHAR(500) NOT NULL,
        Description NVARCHAR(500) NOT NULL,
        CreatedBy INT NOT NULL,
        CreatedDate DATETIME NOT NULL
            DEFAULT GETDATE(),
        Enabled bit default 1,
        CONSTRAINT FK_CreatedBy_ID FOREIGN KEY (ID)
            REFERENCES dbo.users (ID)
    );
    CREATE UNIQUE INDEX UK_Token
        ON dbo.connTokens (Token);
    INSERT INTO dbo.connTokens (Token, ConnectionString, Description, CreatedBy, CreatedDate, Enabled)
    VALUES
        ('806117D6-EE39-4664-B49E-4D069610E818', 'test/12345:abd database=abc', 'dummy entry', 1, '2021-07-07T18:21:38.417Z', 1),
        ('11666A1A-3E54-42C3-A523-9F38EEDD96F3', 'test/1232:abd database=abc', 'dummy entry', 2, '2021-07-07T18:21:38.417Z', 1);

END
-- IF @@ROWCOUNT > 0
IF NOT (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='permissionsMap'))
BEGIN
    CREATE TABLE dbo.permissionsMap (
        UserID int NOT NULL,
        ConnID int NOT NULL,
        Count int NOT NULL
            DEFAULT 0,
        LastUse DATETIME,
        CONSTRAINT PK_permissionsMap PRIMARY KEY CLUSTERED (UserID, ConnID),
    );
    INSERT INTO dbo.permissionsMap (UserID, ConnID, Count, LastUse)
    VALUES
        (1,1,0,GETDATE()),
        (3,1,0,GETDATE()),
        (5,1,0,GETDATE())
        ;
    ALTER TABLE dbo.permissionsMap WITH NOCHECK
    ADD CONSTRAINT FK_userID_ID FOREIGN KEY (UserID)
            REFERENCES dbo.users (ID)
            ON DELETE CASCADE,
        CONSTRAINT FK_sqlID_ID FOREIGN KEY (ConnID)
            REFERENCES dbo.connTokens (ID)
            ON DELETE CASCADE


END

IF NOT (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='ipBlacklist'))
BEGIN
    CREATE TABLE dbo.ipBlacklist (
        IPAddress NVARCHAR(15) NOT NULL
            PRIMARY KEY
    );
END

IF NOT (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='floodProtect'))
BEGIN
    CREATE TABLE dbo.floodProtect (
        UserID int NOT NULL
            PRIMARY KEY,
        Count int NOT NULL
            DEFAULT 1,
        CONSTRAINT FK_floodProtect_userID_ID FOREIGN KEY (UserID)
            REFERENCES dbo.users (ID)
            ON DELETE CASCADE,
    );
END
