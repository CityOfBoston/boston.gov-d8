const config=require("../common/env.config.js"),Output=require("../common/json.responses"),express=require("express"),app=express(),bodyParser=require("body-parser"),AuthorizationRouter=require("../models/authorization/routes.config"),UsersRouter=require("../models/users/routes.config"),ConnectionsRouter=require("../models/connections/routes.config"),ProxyRouter=require("../models/proxies/routes.config"),HealthRouter=require("../models/health/routes.config");app.use(function(e,o,r){return o.set("Cache-Control","no-store"),o.header("Access-Control-Allow-Origin","*"),o.header("Access-Control-Allow-Credentials","true"),o.header("Access-Control-Allow-Methods","GET,HEAD,PUT,PATCH,POST,DELETE"),o.header("Access-Control-Expose-Headers","Content-Length"),o.header("Access-Control-Allow-Headers","Accept, Authorization, Content-Type, X-Requested-With, Range"),"OPTIONS"===e.method?o.sendStatus(200):r()}),app.use(bodyParser.urlencoded({extended:!1})),app.use(bodyParser.json()),app.set("trust proxy",!0),HealthRouter.routesConfig(app),AuthorizationRouter.routesConfig(app),UsersRouter.routesConfig(app),ConnectionsRouter.routesConfig(app),ProxyRouter.routesConfig(app);try{app.listen(config.listen_port,"localhost",function(){console.log("app listening at port %s",config.listen_port)})}catch(e){console.log("app starting error: "+e)}
const ADMIN=config.permissionLevels.ADMIN_USER,SUPER=config.permissionLevels.SUPER_USER,NORMAL=config.permissionLevels.NORMAL_USER,OWNER=config.permissionLevels.OWNER;exports.minimumPermissionLevelRequired=n=>(e,s,r)=>{e=parseInt(e.jwt.role);return n=parseInt(n),e==OWNER||n<=e?r():Output.json_response(s,403)},exports.PermissionLevelRequired=t=>(e,s,r)=>{e=parseInt(e.jwt.role);if("number"==typeof t)t=[t];else if(!Array.isArray(t))return Output.json_response(s,400,{error:"Internal: Bad Role provided"});let n=0;return t.forEach(e=>{n+=parseInt(e)}),0!=n&&(e==OWNER||e&n)?r():Output.json_response(s,403)},exports.onlySameUserOrAdminCanDoThisAction=(e,s,r)=>{var n=parseInt(e.jwt.role),t=parseInt(e.jwt.userid);return e.params&&e.params.userId&&t===parseInt(e.params.userId)||n==ADMIN||n==OWNER?r():Output.json_response(s,403)},exports.sameUserCantDoThisAction=(e,s,r)=>{var n=e.jwt.userid;return e.params.userId!==n?r():Output.json_response(s,403)},exports.isIPAddressAllowed=(e,s,r)=>{let n="";if(e.jwt&&"ipaddresses"in e.jwt)n=e.jwt.ipaddresses;else{if(!(e.body&&"ipaddresses"in e.body))return Output.json_response(s,500,{error:"JWT not initialized"});n=e.body.ipaddresses}if(""==n)return r();n=n.join(";").replace(/localhost/gi,"127.0.0.1").split(";");e=e.ip.replace(/localhost/gi,"127.0.0.1");return n.includes(e)?r():Output.json_response(s,403)};
const flood_time=require("../../common/env.config").flood_time,flood_level=require("../../common/env.config").flood_level;function readUserSession(e){}function incrementFloodCounter(e,o){void 0===o.session||null==o.session?o.session={flood:{}}:void 0===o.session.flood&&(o.session.flood={});let r=o.session.flood;return e in r?r[e]++:r[e]=1,o}exports.verifyRefreshBodyField=(e,o,r)=>e.body&&e.body.refresh_token?r():Output.json_response(o,400,{error:"Missing refresh token"}),exports.validRefreshNeeded=(e,o,r)=>{let t=Buffer.from(e.body.refresh_token,"base64");var s=t.toString();return crypto.createHmac("sha512",e.jwt.refreshKey).update(e.jwt.userid+secret).digest("base64")===s?(e.body=e.jwt,r()):Output.json_response(o,400,{error:"Invalid refresh token"})},exports.validJWTNeeded=(e,o,r)=>{if(""==e.headers.authorization)return Output.json_response(o,401,{error:"Missing Authentication Token"});try{var t=e.headers.authorization.split(" ");return"Bearer"!==t[0]?Output.json_response(o,401):(e.url.includes("/auth/refresh")?e.jwt=jwt.verify(t[1],secret,{ignoreExpiration:!0,maxAge:"1 day"}):e.jwt=jwt.verify(t[1],secret,{maxAge:"1 day"}),r())}catch(e){return"tokenexpirederror"==e.name.toLowerCase()?Output.json_response(o,401,{error:"Expired Token"}):e.toString().toLowerCase().includes("invalid")?Output.json_response(o,401,{error:"Bad Token"}):e.toString().toLowerCase().includes("jwt must be provided")?Output.json_response(o,401,{error:"Missing Authentication Token"}):Output.json_response(o,400,{error:e})}},exports.isFlooding=(e,o,r)=>{var t=Math.round((new Date).getTime()/1e3/flood_time);if(use_count=void 0===e.jwt?(e.body=incrementFloodCounter(t,e.body),parseInt(e.body.session.flood[t])):(e.jwt=incrementFloodCounter(t,e.jwt),parseInt(e.jwt.session.flood[t])),!(use_count<=flood_level))return Output.json_response(o,200,{error:"No Data"});r(),e.jwt};
const crypto=require("crypto"),jwt=require("jsonwebtoken"),jwtSecret=require("../../common/env.config").jwt_secret;let jwtExpiration=require("../../common/env.config").jwt_expiration_in_seconds;const updateExpiration=e=>{"ttl"in e&&""!=e.ttl.toString()&&(jwtExpiration=e.ttl)},setExpiryTime=e=>{let t=0;regex=/h(our)?(s)?/i;let r=e.replace(regex,"*");r.includes("*")&&r.split("*").forEach(e=>{isNaN(e)||""==e||(t+=60*parseInt(e)*60*1e3)}),regex=/m(in)?(ute)?(s)?/i,r=r.replace(regex,"%"),r.includes("%")&&r.split("%").forEach(e=>{isNaN(e)||""==e||(t+=60*parseInt(e)*1e3)}),regex=/s(ec)?(ond)?(s)?/i,r=r.replace(regex,"#"),r.includes("#")&&r.split("#").forEach(e=>{isNaN(e)||""==e||(t+=1e3*parseInt(e))});e=new Date;return parseInt((e.getTime()+t)/1e3)};exports.login=(r,o)=>{try{var n=r.body.userid+jwtSecret,s=crypto.randomBytes(16).toString("base64"),i=crypto.createHmac("sha512",s).update(n).digest("base64");let e;r.body.refreshKey=s,e="exp"in r.body?(r.body.exp=setExpiryTime(jwtExpiration),jwt.sign(r.body,jwtSecret)):(updateExpiration(r.body),jwt.sign(r.body,jwtSecret,{expiresIn:jwtExpiration}));let t=Buffer.from(i);var p=t.toString("base64");return Output.json_response(o,200,{userid:r.body.userid,authToken:e,refreshToken:p})}catch(e){return Output.json_response(o,400,{error:e})}},exports.refresh_token=(e,t)=>{try{e.body=e.jwt,console.log("JWT: ",e.jwt),updateExpiration(e.body);var r=jwt.sign(e.body,jwtSecret,{expiresIn:jwtExpiration});console.log("JWT2: ",e.jwt);return Output.json_response(t,200,{userid:e.body.userid,authToken:r,refreshToken:"a"})}catch(e){return console.log(e),Output.json_response(t,400,{error:e})}};
const AuthorizationController=require("./authorization.controller"),AuthValidationMiddleware=require("./auth.validation.middeware"),VerifyUserMiddleware=require("./verify.user.middleware"),PermissionMiddleware=require("./auth.permission.middleware");exports.routesConfig=function(e){e.post("/auth",[VerifyUserMiddleware.hasAuthValidFields,VerifyUserMiddleware.isPasswordAndUserMatch,PermissionMiddleware.isIPAddressAllowed,VerifyUserMiddleware.isUserEnabled,AuthValidationMiddleware.isFlooding,AuthorizationController.login]),e.post("/auth/refresh",[AuthValidationMiddleware.validJWTNeeded,AuthValidationMiddleware.verifyRefreshBodyField,AuthValidationMiddleware.validRefreshNeeded,PermissionMiddleware.isIPAddressAllowed,AuthValidationMiddleware.isFlooding,AuthorizationController.login])};
exports.hasAuthValidFields=(s,e,r)=>{let o=[];return s.body?(s.body.username||o.push("Missing username/email field"),s.body.password||o.push("Missing password field"),o.length?Output.json_response(e,400,{error:o.join(". ")}):r()):Output.json_response(e,400,{error:"Missing authentication payload"})},exports.isPasswordAndUserMatch=(o,n,a)=>{UserModel.findByUsername(o.body.username).then(s=>{if(s&&s!=[]&&s[0]){var e=s[0].Password.split("$"),r=e[0];return crypto.createHmac("sha512",r).update(o.body.password).digest("base64")===e[1]?(o.body={userid:s[0].ID,username:s[0].Username,role:s[0].Role,enabled:s[0].Enabled,ipaddresses:s[0].IPAddresses.split(";")||"",session:s[0].Session,ttl:s[0].TTL},a()):Output.json_response(n,400,{error:"Invalid username or password"})}return Output.json_response(n,400,{error:"Invalid username or password"})}).catch(s=>Output.json_response(n,400,{error:s}))},exports.isUserEnabled=(s,e,r)=>s.body.enabled?r():Output.json_response(e,401,{error:"User Disabled"});
const ConnModel=require("./connections.model"),Output=require("../../common/json.responses");exports.insert=(e,n)=>{connData=e.body,"userid"in e.body?connData.createdBy=e.body.userid:"userid"in e.jwt&&(connData.createdBy=e.jwt.userid),(!1 in connData||""==connData.description)&&(connData.description=`Created by ${e.jwt.username}`),ConnModel.create(connData).then(e=>Output.json_response(n,201,{connToken:e})).catch(e=>(console.log("ConnError: "+e),Output.json_response(n,400,{error:e})))},exports.list=(e,n)=>{let o=10,t=0;e.query&&(e.query.page&&(e.query.page=parseInt(e.query.page),t=Number.isInteger(e.query.page)?e.query.page:0),e.query.limit&&(e.query.limit=parseInt(e.query.limit),o=Number.isInteger(e.query.limit)?e.query.limit:10)),ConnModel.list(o,t).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.get=(e,n)=>{ConnModel.findByToken(e.params.token).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.update=(e,n)=>{ConnModel.update(e.params.token,e.body).then(e=>Output.json_response(n,204)).catch(e=>Output.json_response(n,400,{error:e}))},exports.disable=(e,n)=>{ConnModel.disableByToken(e.params.token).then(e=>Output.json_response(n,204,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.getUserConnections=(e,n)=>{isNaN(e.params.userId)?ConnModel.findConnectionsByUsername(e.params.userId).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e})):ConnModel.findConnectionsByUserId(e.params.userId).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.getConnectionUsers=(e,n)=>{ConnModel.findUsersByToken(e.params.token).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.insertMapping=(e,n)=>{ConnModel.createMapping(e.params.token,e.params.userid).then(e=>Output.json_response(n,201,e)).catch(e=>Output.json_response(n,400,{error:e}))},exports.deleteMapping=(e,n)=>{ConnModel.deleteMapping(e.params.token,e.params.userid).then(e=>Output.json_response(n,204)).catch(e=>Output.json_response(n,400,{error:e}))};
const sql_exec=require("../../common/services/tedious.exec.service"),connSchema={id:0,connectionString:"",description:"",createdDate:"GETDATE()",createdBy:0,enabled:1};String.prototype.trimRight=function(e){return void 0===e&&(e="s"),this.replace(new RegExp("["+e+"]+$"),"")},exports.findByToken=e=>new Promise((o,s)=>{sql=`SELECT
              ID,
              Token,
              ConnectionString,
              Description,
              CreatedDate,
              CreatedBy,
              Enabled
            FROM dbo.connTokens
           WHERE token = '${e}';`;try{sql_exec.exec(sql,function(e,n){n?n.includes("Conversion failed when converting from a character string to uniqueidentifier")?s("Token not found"):s(n):e[0]?o(e[0]):s("Token not found")})}catch(e){s(e)}}),exports.create=n=>new Promise((o,s)=>{void 0!==n.token&&""!=n.token.trim()||delete n.token,void 0!==n.description&&""!=n.description.trim()||(n.description="API Insert"),void 0!==n.createdDate&&delete n.createdDate,(!1 in n||0==n.createdBy||""==n.createdBy)&&s("Must identify the creators UserID");var e={...connSchema,...n};sql=`
    INSERT INTO dbo.connTokens(
        Token,
        ConnectionString,
        Description,
        CreatedDate,
        CreatedBy,
        Enabled)
      VALUES(
        NEWID(),
        '${e.connectionString}',
        '${e.description}',
        ${e.createdDate},
        ${e.createdBy},
        ${e.enabled}
      );
    SELECT Token as connTokens
      FROM dbo.connTokens
    WHERE ID = @@IDENTITY;`,sql_exec.exec(sql,function(e,n){n?s(n):o(e[1][0].connToken)})}),exports.list=(e,n)=>new Promise((o,s)=>{sql=`SELECT
              tok.ID,
              tok.Token,
              '****' as ConnectionString,
              tok.Description,
              tok.CreatedDate,
              users.Username,
              tok.Enabled
           FROM dbo.connTokens tok
              LEFT JOIN dbo.users users on tok.createdBy = users.ID
           ORDER BY tok.ID ASC
           OFFSET ${e*n} ROWS FETCH NEXT ${e} ROWS ONLY;`,sql_exec.exec(sql,function(e,n){n?s(n):o(e[0])})}),exports.update=(t,c)=>{let i="Not Found";return new Promise((o,s)=>{let e="";for(const n in c)"createdBy"==n||"enabled"==n?e+=`${n} = ${c[n]},`:e+=`${n} = '${c[n]}',`;e=e.trimRight(","),sql=`
      IF EXISTS (SELECT * FROM dbo.connTokens WHERE token = '${t}')
        BEGIN
          SELECT 'Updated' as action;

          UPDATE dbo.connTokens
            SET ${e}
          WHERE token = '${t}';
        END

      ELSE
        SELECT '${i}' as action;
    `,sql_exec.exec(sql,function(e,n){n?s(n):e[0][0].action==i?s(i):o(e[0][0])})})},exports.disableByToken=e=>{let t="Not Found";return new Promise((o,s)=>{sql=`
        UPDATE dbo.connTokens
            SET Enabled = 0
          WHERE token = '${e}';
        IF @@ROWCOUNT > 0
          SELECT 'Removed' as action;
        ELSE
          SELECT '${t}' as action;    `,sql_exec.exec(sql,function(e,n){n?s(n):e[1][0].action==t?s(t):o(e[1][0])})})},exports.findConnectionsByUserId=e=>new Promise((o,s)=>{sql=`SELECT
              u.Username,
              u.ID as userid,
              conn.ID as connid,
              conn.Token,
              conn.ConnectionString,
              conn.Description,
              conn.Enabled,
              map.Count,
              map.LastUse
          FROM dbo.users u
            INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
            INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
          WHERE u.ID = '${e}';`,sql_exec.exec(sql,function(e,n){n?s(n):o(e[0])})}),exports.findConnectionsByUsername=e=>new Promise((o,s)=>{sql=`SELECT
              u.Username,
              u.ID as userid,
              conn.ID as connid,
              conn.Token,
              conn.ConnectionString,
              conn.Description,
              conn.Enabled,
              map.Count,
              map.LastUse
           FROM dbo.users u
             INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
             INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
           WHERE u.Username = '${e}';`,sql_exec.exec(sql,function(e,n){n?s(n):o(e[0])})}),exports.findUsersByToken=e=>new Promise((o,s)=>{sql=`SELECT
              u.ID,
              u.Username,
              u.Enabled,
              map.Count,
              map.LastUse
           FROM dbo.users u
             INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
             INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
           WHERE conn.Token= '${e}';`,sql_exec.exec(sql,function(e,n){n?s(n):o(e[0])})}),exports.createMapping=(e,n)=>new Promise((o,s)=>{sql=`INSERT INTO dbo.permissionsMap (
                UserID,
                ConnID)
              SELECT ${n}, tok.ID
                FROM dbo.connTokens tok
              WHERE Token = '${e}';`,sql_exec.exec(sql,function(e,n){n?s(n):o({result:"Done"})})}),exports.deleteMapping=(e,n)=>{let t="Not Found";return new Promise((o,s)=>{sql=`
      DELETE dbo.permissionsMap
      FROM dbo.permissionsMap map
        INNER JOIN dbo.connTokens tok ON map.ConnID = tok.ID
        WHERE map.UserID = ${n} AND tok.Token = '${e}'

      IF @@ROWCOUNT > 0
        SELECT 'Removed' as action;
      ELSE
        SELECT '${t}' as action;
    `,sql_exec.exec(sql,function(e,n){n?s(n):e[1][0].action==t?s(t):o(e[1][0])})})},exports.incrementConnectionUse=(e,n)=>new Promise((o,s)=>{sql=`
      UPDATE dbo.permissionsMap
      SET
        Count = Count + 1,
        LastUse = GETDATE()
      FROM dbo.permissionsMap
        INNER JOIN dbo.connTokens on dbo.permissionsMap.ConnID = dbo.connTokens.ID
      WHERE dbo.connTokens.Token= '${e}';
    `,sql_exec.exec(sql,function(e,n){n?s(n):o({result:"Done"})})});
const ConnModel=require("./connections.model"),sql_exec=require("../../common/services/tedious.exec.service"),Output=require("../../common/json.responses"),config=require("../../common/env.config"),ADMIN=config.permissionLevels.ADMIN_USER,OWNER=config.permissionLevels.OWNER;exports.isConnectionEnabled=(e,o,n)=>{n()},exports.canThisUserUseThisConnection=(e,n,s)=>{var o=parseInt(e.jwt.role);required_role=parseInt(ADMIN),o==OWNER||o>=required_role?s():(sql=`
    SELECT COUNT(*) as count
      FROM permissionsMap perm
        INNER JOIN dbo.connTokens tok ON perm.connID = tok.id
    WHERE userid = ${e.jwt.userid} and tok.token = '${e.params.token}';
    `,sql_exec.exec(sql,function(e,o){return o?Output.json_response(n,400,{error:o}):0==e[0][0].count?Output.json_response(n,403):void s()}))};
const ConnController=require("./connections.controller"),ValidationMiddleware=require("../authorization/auth.validation.middeware"),PermissionMiddleware=require("../authorization/auth.permission.middleware"),ConnPermissionMiddleware=require("./connections.permission.middleware"),config=require("../../common/env.config");exports.routesConfig=function(e){e.post("/connection",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),ConnController.insert]),e.get("/connections",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),ConnController.list]),e.get("/connections/:token",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,ConnPermissionMiddleware.canThisUserUseThisConnection,ConnController.get]),e.patch("/connections/:token",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(SUPER),ConnController.update]),e.delete("/connections/:token",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(SUPER),ConnController.disable]),e.get("/users/:userId/connections",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,ConnController.getUserConnections]),e.get("/connection/:token/users",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,ConnPermissionMiddleware.canThisUserUseThisConnection,ConnController.getConnectionUsers]),e.post("/connection/:token/user/:userid",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(SUPER),ConnController.insertMapping]),e.delete("/connection/:token/user/:userid",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(SUPER),ConnController.deleteMapping])};
exports.ping=(s,n)=>sql_conn?Output.json_response(n,200,{}):Output.json_response(n,404);
const HealthController=require("./health.controller");exports.routesConfig=function(o){o.get("/admin/ok",[HealthController.ping])};
const ConnModel=require("../connections/connections.model"),Output=require("../../common/json.responses");function fetchConnectionstringFromToken(e){return new Promise((o,n)=>{ConnModel.findByToken(e).then(e=>{e==[]&&n("Token not found"),o(e[0])}).catch(e=>{n(e)})})}exports.query=(o,n)=>{const t=require(`./${o.params.driver}/${o.params.driver}.model`);fetchConnectionstringFromToken(o.body.token).then(e=>e.Enabled?(o.body.connectionString=e.ConnectionString,void t.exec(o.body).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))):Output.json_response(n,403)).catch(e=>Output.json_response(n,400,{error:e}))},exports.select=(o,n)=>{const t=require(`./${o.params.driver}/${o.params.driver}.model`);o.body&&(o.body.page&&(o.body.page=parseInt(o.body.page),o.body.page=Number.isInteger(o.body.page)?o.body.page:0),o.body.limit&&(o.body.limit=parseInt(o.body.limit),o.body.limit=Number.isInteger(o.body.limit)?o.body.limit:100)),fetchConnectionstringFromToken(o.body.token).then(e=>{o.body.connectionString=e.ConnectionString,t.select(o.body).then(e=>Output.json_response(n,200,e)).catch(e=>Output.json_response(n,400,{error:e}))}).catch(e=>Output.json_response(n,400,{error:e}))};
const Output=require("../../common/json.responses");exports.IsPayloadValid=(e,t,s)=>"body"in e?"statement"in e.body&&""!=e.body.statement&&"token"in e.body&&""!=e.body.token?s():Output.json_response(t,400,{error:"Malformed Payload"}):Output.json_response(t,400,{error:"Missing Payload"}),exports.IsSelectQueryValid=(t,s,o)=>{if("body"in t&&"statement"in t.body){let e=t.body.statement.trim();return e.endsWith(";")&&(e=e.split(";")[0]),e.includes(";")?Output.json_response(s,400,{error:"Cannot pass multiple commands"}):e.toLowerCase().includes("select")?"body"in t&&"limit"in t.body&&!e.toLowerCase().includes("order")?Output.json_response(s,400,{error:"Paged query must have order by clause"}):o():Output.json_response(s,400,{error:"Statement must be a select query"})}return Output.json_response(s,400,{error:"Missing statement in payload"})};
const ProxyController=require("./proxy.controller"),ValidationMiddleware=require("../authorization/auth.validation.middeware"),ProxyValidationMiddleware=require("./proxy.validation.middeware"),PermissionMiddleware=require("../authorization/auth.permission.middleware"),config=require("../../common/env.config"),ADMIN=config.permissionLevels.ADMIN_USER,SUPER=config.permissionLevels.SUPER_USER,NORMAL=config.permissionLevels.NORMAL_USER,OWNER=config.permissionLevels.OWNER;exports.routesConfig=function(e){e.post("/query/:driver",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,ProxyValidationMiddleware.IsPayloadValid,PermissionMiddleware.minimumPermissionLevelRequired(NORMAL),ProxyController.query]),e.post("/select/:driver",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),ProxyValidationMiddleware.IsSelectQueryValid,ProxyController.select])};
const UsersController=require("./users.controller"),ValidationMiddleware=require("../authorization/auth.validation.middeware"),PermissionMiddleware=require("../authorization/auth.permission.middleware"),config=require("../../common/env.config"),ADMIN=config.permissionLevels.ADMIN_USER,SUPER=config.permissionLevels.SUPER_USER,NORMAL=config.permissionLevels.NORMAL_USER,OWNER=config.permissionLevels.OWNER;exports.routesConfig=function(e){e.post("/users",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),UsersController.insert]),e.get("/users",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),UsersController.list]),e.get("/users/:userId",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,UsersController.get]),e.patch("/users/:userId",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,UsersController.update]),e.delete("/users/:userId",[ValidationMiddleware.validJWTNeeded,ValidationMiddleware.isFlooding,PermissionMiddleware.isIPAddressAllowed,PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),UsersController.disable])};
const UserModel=require("./users.model"),crypto=require("crypto"),Output=require("../../common/json.responses");exports.insert=(e,r)=>{var s=crypto.randomBytes(16).toString("base64"),t=crypto.createHmac("sha512",s).update(e.body.password).digest("base64");e.body.password=s+"$"+t,UserModel.create(e.body).then(e=>Output.json_response(r,201,{id:e})).catch(e=>Output.json_response(r,400,{error:e}))},exports.list=(e,r)=>{let s=10,t=0;e.query&&(e.query.page&&(e.query.page=parseInt(e.query.page),t=Number.isInteger(e.query.page)?e.query.page:0),e.query.limit&&(e.query.limit=parseInt(e.query.limit),s=Number.isInteger(e.query.limit)?e.query.limit:10)),UserModel.list(s,t).then(e=>Output.json_response(r,200,e)).catch(e=>Output.json_response(r,400,{error:e}))},exports.get=(e,r)=>{isNaN(e.params.userId)?UserModel.findByUsername(e.params.userId).then(e=>Output.json_response(r,200,e)).catch(e=>Output.json_response(r,400,{error:e})):UserModel.findByUserId(e.params.userId).then(e=>Output.json_response(r,200,e)).catch(e=>Output.json_response(r,400,{error:e}))},exports.update=(e,r)=>{var s,t;e.body.password&&(s=crypto.randomBytes(16).toString("base64"),t=crypto.createHmac("sha512",s).update(e.body.password).digest("base64"),e.body.password=s+"$"+t),UserModel.update(e.params.userId,e.body).then(e=>Output.json_response(r,204)).catch(e=>Output.json_response(r,400,{error:e}))},exports.disable=(e,r)=>{UserModel.disableById(e.params.userId).then(e=>Output.json_response(r,204))};
const sql_exec=require("../../common/services/tedious.exec.service");let defaultTTL=require("../../common/env.config").jwt_expiration_in_seconds;const userSchema={userid:0,username:"",password:"",ipaddresses:"",enabled:1,role:1,session:"",ttl:defaultTTL};String.prototype.trimRight=function(e){return void 0===e&&(e="s"),this.replace(new RegExp("["+e+"]+$"),"")},exports.findByUsername=e=>new Promise((o,r)=>{sql=`SELECT ID, Username, Password, IPAddresses, Enabled, Role, Session, TTL
           FROM dbo.users
           WHERE Username = '${e}';`,sql_exec.exec(sql,function(e,s){s?(console.log("error"+s),r(s)):void 0===e||e==[]||""==e?r("Username not found"):o(e[0])})}),exports.findByUserId=e=>new Promise((o,r)=>{sql=`SELECT ID, Username, '*****' as Password, IPAddresses, Enabled, Role, Session, TTL
           FROM dbo.users
           WHERE ID = ${e};`,sql_exec.exec(sql,function(e,s){s?r(s):o(e[0])})}),exports.create=s=>new Promise((o,r)=>{userSchema.IPAddresses="";var e={...userSchema,...s};sql=`INSERT INTO dbo.users(
              Username,
              Password,
              IPAddresses,
              Enabled,
              Role,
              TTL)
            VALUES(
              '${e.username}',
              '${e.password}',
              '${e.ipaddresses}',
              ${e.enabled},
              '${e.role}',
              '${e.ttl}'
            );
            SELECT @@IDENTITY as 'id';`,sql_exec.exec(sql,function(e,s){s?r(s):o(e[1][0].id)})}),exports.list=(e,s)=>new Promise((o,r)=>{sql=`SELECT ID, Username, '*****' as Password, IPAddresses, Enabled, Role, TTL
           FROM dbo.users
           ORDER BY ID ASC
           OFFSET ${e*s} ROWS FETCH NEXT ${e} ROWS ONLY;`,sql_exec.exec(sql,function(e,s){s?r(s):o(e[0])})}),exports.update=(n,E)=>{let d="Not Found";return new Promise((o,r)=>{let e="";for(const s in E)e+=`${s} = '${E[s]}',`;e=e.trimRight(","),sql=`
      IF EXISTS (SELECT * FROM dbo.users WHERE ID = ${n})
        BEGIN
          SELECT 'Updated' as action;

          UPDATE dbo.users
            SET ${e}
          WHERE ID = ${n};
        END

      ELSE
        SELECT '${d}' as action;
    `,sql_exec.exec(sql,function(e,s){s?r(s):e[0][0].action==d?r(d):o(e[0][0])})})},exports.disableById=e=>{let n="Not Found";return new Promise((o,r)=>{sql=`
      IF EXISTS (SELECT * FROM dbo.users WHERE ID = ${e})
        BEGIN
          SELECT 'Removed' as action;

          UPDATE dbo.users
            SET Enabled = 0
          WHERE ID = ${e};
        END

      ELSE
        SELECT '${n}' as action;
    `,sql_exec.exec(sql,function(e,s){s?r(s):e[0][0].action==n?r(n):o(e[0][0])})})};
const TediousConnection=require("tedious").Connection,colors=require("colors");let callback,connection;function connected(n){n?(console.log(`\u2716 MSSQL Proxy Connection Error: ${n}`.red),callback("error",{error:`MSSql proxy connection error: ${n}`})):(console.log("✔".green+" MSSQL Proxy Connected"),callback("connected",connection))}function end(){console.log("MSSQL Connection Closed"),callback("disconnected",{})}function error(n){console.log(`\u2716 MSSQL Proxy Error: ${n.number}: ${n.message}`.red)}function info(n){}function debug(n){}exports.connect=(n,o)=>{callback=o,connection=new TediousConnection(n),connection.on("infoMessage",info),connection.on("errorMessage",error),connection.on("end",end),connection.on("debug",debug),connection.connect(connected)},exports.connection=connection;
const Request=require("tedious").Request,mssql=require("./mssql.connect.service");let rows=[],statements=0,callback,errors=!1;function requestDone(e,t,o){if(statements+=1,0<e){let s=[];o.forEach(e=>{lrow=e;for(const t in lrow)lrow[t]=lrow[t].value;s.push(lrow)}),rows.push(s)}}function tediousReturn(e){errors||(e&&void 0!==e?(callback([],"Statement "+(statements+1)+" failed: "+e),errors=!0):callback(rows))}function statementComplete(e,t){errors||e&&void 0!==e&&(callback([],"Statement "+(statements+1)+" failed: "+e),errors=!0)}exports.exec=(e,o,t)=>{callback=t,errors=!1,statements=0,rows=[],o=o.toString(),o=`use ${e.options.database};\n ${o};`,delete e.options.schema,mssql.connect(e,function(e,t){const s=new Request(o,statementComplete);s.on("doneInProc",requestDone),s.on("error",tediousReturn),s.on("requestCompleted",tediousReturn),"connected"==e?t.execSql(s):"error"==e&&callback([],t)})},exports.statementsExecuted=statements;
const type=require("os")["type"],config=require("process")["config"],mssqlexec=require("./mssql.exec.service"),bodySchema={connectionString:"",sqlStatement:"",limit:100,page:0},connstr={server:"",port:1433,database:"",userName:"",password:""};exports.connstr=connstr;let tediousConfigSchema={server:"",options:{port:1433,database:"",schema:"dbo",trustServerCertificate:!0,requestTimeout:3e4,useColumnNames:!0,rowCollectionOnDone:!0},authentication:{type:"default",options:{userName:"",password:""}}};const unpackSQL=(e,t)=>{var s;for(const n in t)Object.hasOwnProperty.call(t,n)&&(s=t[n],e=e.replace(`{${n}}`,s));return e},makeTediousConfig=e=>{let t;t=tediousConfigSchema;e=JSON.parse(e);return t.server=e.host,t.authentication.options.userName=e.user,t.authentication.options.password=e.password,"port"in e&&(t.options.port=parseInt(e.port)),"schema"in e&&(t.options.schema=parseInt(e.schema)),"database"in e?t.options.database=e.database:delete t.options.database,t};exports.exec=t=>new Promise((s,n)=>{sql=unpackSQL(t.statement,t.args);var e=makeTediousConfig(t.connectionString);mssqlexec.exec(e,sql,function(e,t){t?n(t):1==e.length?s(e[0]):s(e)})}),exports.select=o=>new Promise((s,n)=>{let e=o.statement;e.endsWith(";")&&(e=e.split(";")[0]),e=unpackSQL(e,o.args),e.toLowerCase().includes("offset")||(e=`${e}\n OFFSET {offset} ROWS FETCH NEXT {limit} ROWS ONLY;`,paging={limit:parseInt(o.limit),offset:parseInt(o.page)*parseInt(o.limit)},e=unpackSQL(e,paging));var t=makeTediousConfig(o.connectionString);mssqlexec.exec(t,e,function(e,t){t?n(t):s(e[0])})});
module.exports={port:3600,appEndpoint:"http://localhost:3600",apiEndpoint:"http://localhost:3600",jwt_secret:"myS33!!creeeT",jwt_expiration_in_seconds:"180s",flood_time:30,flood_level:60,environment:"dev",permissionLevels:{NORMAL_USER:1,SUPER_USER:4,ADMIN_USER:2048,OWNER:4096},apiConfig:{server:"MSSQL_CMDB",options:{port:1433,database:"dbconnector",trustServerCertificate:!0,requestTimeout:3e4,useColumnNames:!0,rowCollectionOnDone:!0},authentication:{type:"default",options:{userName:"dbconnector",password:"dbc0nnector@COB"}}}};
exports.json_response=(s,t,e)=>(e=403===t?"":e)&&void 0!==e&&""!=e?("string"!=typeof e&&(e=JSON.stringify(e)),s.status(parseInt(t)).send(e)):s.status(parseInt(t)).send();
const Connection=require("tedious").Connection,config=require("../env.config").apiConfig;let state="disconnected";const connection=new Connection(config);function connected(n){n&&(state="error",console.log("Connection failed: "+n.message),console.log("App terminated due to System DB connectivity errors"),process.exit(0)),console.log("System DB Connected"),process.stdin.resume(),process.stdin.on("data",function(n){exec(n)}),process.stdin.on("end",function(){state="connected",info({number:1,message:"System DB Connected"})}),process.stderr.on("error",function(){error({number:0,message:"STDIN Error"})})}function end(){info({number:0,message:"System DB Connection closed"}),process.exit(0)}function info(n){}function error(n){}function debug(n){}connection.connect(connected),connection.on("infoMessage",info),connection.on("errorMessage",error),connection.on("end",end),connection.on("debug",debug),exports.connection=connection,exports.state=state;
const sql_conn=require("../../common/services/tedious.connect.service").connection,Request=require("tedious").Request;let rows=[],statements=0,callback,errors=!1;function requestDone(e,t,s){if(statements+=1,0<e){let o=[];s.forEach(e=>{lrow=e;for(const t in lrow)lrow[t]=lrow[t].value;o.push(lrow)}),rows.push(o)}}function tediousReturn(e){errors||(e&&void 0!==e?(callback([],"Statement "+(statements+1)+" failed: "+e),errors=!0):callback(rows))}function statementComplete(e,t){errors||e&&void 0!==e&&(callback([],"Statement "+(statements+1)+" failed: "+e),errors=!0)}exports.exec=(e,t)=>{callback=t,errors=!1,statements=0,rows=[],e=e.toString();const o=new Request(e,statementComplete);o.on("doneInProc",requestDone),o.on("error",tediousReturn),o.on("requestCompleted",tediousReturn),sql_conn.execSql(o)},exports.statementsExecuted=statements;