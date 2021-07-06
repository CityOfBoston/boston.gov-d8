IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[permissionsMap]') AND type in (N'U'))
DROP TABLE [dbo].[permissionsMap];
-- GO;
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[connTokens]') AND type in (N'U'))
DROP TABLE [dbo].[connTokens];
-- GO;
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[ipBlacklist]') AND type in (N'U'))
DROP TABLE [dbo].[ipBlacklist];
-- GO;
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[floodProtect]') AND type in (N'U'))
DROP TABLE [dbo].[floodProtect];
-- GO;
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[users]') AND type in (N'U'))
DROP TABLE [dbo].[users];
-- GO;