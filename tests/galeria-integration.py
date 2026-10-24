"""Prueba HTTP con MariaDB temporal. No usa config.php ni datos del hosting."""
from pathlib import Path
import tempfile, subprocess, shutil, time, urllib.request, urllib.parse, urllib.error, http.cookiejar, re, json

root=Path(__file__).resolve().parents[1]
mysql=Path('C:/xampp/mysql/bin')
php=shutil.which('php')
dbport=33319
webport=18919
checks=0
def check(ok, message):
    global checks
    if not ok: raise AssertionError(message)
    checks+=1

with tempfile.TemporaryDirectory(prefix='galeria-test-') as folder:
    tmp=Path(folder); data=tmp/'db'; app=tmp/'app'; app.mkdir()
    for file in (root/'php-app').iterdir():
        if file.is_file() and file.name not in ('config.php','.env'): shutil.copy(file,app/file.name)
    subprocess.run([str(mysql/'mysql_install_db.exe'),f'--datadir={data}',f'--port={dbport}','--silent'],check=True,capture_output=True)
    log=(tmp/'services.log').open('w')
    db=subprocess.Popen([str(mysql/'mysqld.exe'),'--no-defaults',f'--basedir={mysql.parent}',f'--datadir={data}',f'--port={dbport}','--bind-address=127.0.0.1','--innodb-buffer-pool-size=32M'],stdout=log,stderr=log)
    web=None
    def sql(statement):
        p=subprocess.run([str(mysql/'mysql.exe'),'--host=127.0.0.1',f'--port={dbport}','--user=root','--default-character-set=utf8mb4','--batch','--skip-column-names'],input=statement,text=True,encoding='utf-8',capture_output=True)
        if p.returncode: raise RuntimeError(p.stderr)
        return p.stdout.strip()
    try:
        for _ in range(40):
            try: sql('SELECT 1'); break
            except RuntimeError: time.sleep(.25)
        schema=(root/'database/galeria-mariadb-phpmyadmin.sql').read_text(encoding='utf-8-sig')
        sql('CREATE DATABASE mixshopc_galeria;\n'+schema)
        sql('USE mixshopc_galeria;\n'+(root/'database/migracion-categorias-galeria.sql').read_text(encoding='utf-8-sig'))
        password='Local-test-only-29!'
        hashed=subprocess.check_output([php,'-r',f'echo password_hash({json.dumps(password)}, PASSWORD_DEFAULT);'],text=True)
        sql("USE mixshopc_galeria; INSERT INTO usuarios(nombre,email,password_hash,rol) VALUES('Prueba','root@example.test','"+hashed+"','superadmin');")
        (app/'config.php').write_text("<?php return ['host'=>'127.0.0.1','port'=>"+str(dbport)+",'database'=>'mixshopc_galeria','user'=>'root','password'=>''];",encoding='utf-8')
        web=subprocess.Popen([php,'-d',f'session.save_path={tmp}','-S',f'127.0.0.1:{webport}','-t',str(app)],stdout=log,stderr=log)
        session=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        def request(path,fields=None,client=None):
            req=urllib.request.Request(f'http://127.0.0.1:{webport}/'+path,data=urllib.parse.urlencode(fields).encode() if fields is not None else None)
            try:
                with (client or session).open(req) as response: return response.status,response.read().decode()
            except urllib.error.HTTPError as e: return e.code,e.read().decode()
        for _ in range(30):
            try: request('login.php'); break
            except urllib.error.URLError: time.sleep(.2)
        request('login.php',{'email':'root@example.test','password':password})
        status,body=request('galeria-admin.php'); check(status==200 and 'Activar módulo' in body,'Pantalla de instalación')
        token=re.search(r'name="csrf" value="([^"]+)"',body).group(1)
        check(request('galeria-admin.php',{'action':'setup','csrf':'incorrecto'})[0]==403,'CSRF rechazado')
        status,body=request('galeria-admin.php',{'action':'setup','csrf':token}); check(status==200 and 'Registrar tienda y administrador' in body,'Activación GPS')
        request('galeria-admin.php',{'action':'category','csrf':token,'nombre':'Artesanías','descripcion':'Arte','orden':'1','estado':'activa'})
        cat=sql("USE mixshopc_galeria; SELECT id FROM galeria_categorias WHERE nombre='Artesanías';")
        fields={'action':'store','csrf':token,'nombre':'Tienda prueba','eslogan':'Arte cusqueño','telefono_whatsapp':'+51987654321','direccion':'Sol 100','ciudad':'Cusco','modalidad':'administrada','porcentaje_comision':'5.50','galeria_categoria_id':cat,'estado':'activa','latitud':'-13.5167','longitud':'-71.9781','admin_nombre':'Comerciante','admin_email':'store@example.test','admin_password':password}
        status,body=request('galeria-admin.php',fields); check('Cambios guardados correctamente' in body,'Creación atómica')
        store=sql('USE mixshopc_galeria; SELECT id FROM tiendas LIMIT 1;')
        check(sql('USE mixshopc_galeria; SELECT COUNT(*) FROM usuarios WHERE tienda_id='+store)== '1','Administrador asociado')
        slug=sql('USE mixshopc_galeria; SELECT slug FROM tiendas LIMIT 1;')
        check('Tienda prueba' in request('index.php?categoria='+cat)[1],'Filtro categoría')
        check('No encontramos tiendas' in request('index.php?q=no-existe')[1],'Búsqueda sin resultados')
        check('Ver ubicación GPS' in request('tienda.php?slug='+slug)[1],'GPS en catálogo')
        fields['nombre']='Duplicada'; status,body=request('galeria-admin.php',fields)
        check('correo ya está registrado' in body and sql('USE mixshopc_galeria; SELECT COUNT(*) FROM tiendas;')=='1','Rollback por correo duplicado')
        status,body=request('galeria-admin.php?editar='+store)
        check('value="administrada" selected' in body and '5.50' in body,'Edición conserva valores')
        fields.update({'id':store,'nombre':'Tienda editada','estado':'suspendida'}); fields.pop('admin_password')
        request('galeria-admin.php',fields)
        check('Tienda editada' not in request('index.php')[1],'Suspensión oculta tienda')
        check(sql('USE mixshopc_galeria; SELECT slug FROM tiendas LIMIT 1;')==slug,'Enlace estable')
        fields['estado']='activa'; request('galeria-admin.php',fields)
        request('galeria-admin.php',{'action':'category','csrf':token,'id':cat,'nombre':'Artesanías','descripcion':'Arte','orden':'1','estado':'inactiva'})
        check('Tienda editada' not in request('index.php')[1],'Categoría oculta tiendas')
        merchant=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        request('login.php',{'email':'store@example.test','password':password},merchant)
        check(request('galeria-admin.php',client=merchant)[0]==403,'Administrador de tienda sin acceso global')
        print(f'{checks} pruebas HTTP/MariaDB aprobadas.')
    finally:
        if web: web.terminate(); web.wait(timeout=10)
        db.terminate(); db.wait(timeout=10); log.close()
