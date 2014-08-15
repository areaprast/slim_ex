<?php
/**
 * Bagian pertama : Membutuhkan Slim Framework
 * 
 * Jika tidak menggunakan Composer, kita perlu menambahkan 
 * Slim Framework dan mendaftarkan autoloader PSR-0.
 *
 * Jika kita menggunakan Composer, kita boleh melewati 
 * bagian ini.
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Bagian kedua : Contoh menggunakan aplikasi Slim
 *
 * Memberikan contoh menggunakan aplikasi Slim dengan pengaturan default.
 */
$app = new \Slim\Slim();

/**
 * Bagian Konfigurasi Aplikasi
 *
 * Cara pertama yaitu sebelum inisiasi program.
 */
//$app = new \Slim\Slim(array('debug' => true));
 
/**
 * Bagian ketiga : Mendefinisikan rute aplikasi Slim
 * 
 * Disini kita mendefinisikan rute yang merespon method dari permintaan HTTP
 */

// GET route  ---->>>>>>> Untuk READ atau mengambil data
$app->get('/', function () {
    echo "<h1>Halo, di sini Slim</h1>";
});

$app->get('/product/kode=:code&nama=:name', function ($code,$name) { 
    echo "Kode            : $code<br>"; 
    echo "Kategori Produk : $name<br>";
    echo "Nama Produk     : $name<br>";
});

function getConnection() {
    $dbhost = "127.0.0.1";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "slim_ex";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

function validateApiKey($key) {
    $sql = "select * FROM tbl_api_reg where api_key = '".$key."'";
    $db  = getConnection();
    $sth = $db->prepare($sql);
    $sth->execute();
    return $sth->rowCount();
}

$authKey = function ($route) {
    $app = \Slim\Slim::getInstance();
    $routeParams = $route->getParams();
    if (validateApiKey($routeParams["key"])==0)
    {
        $app->halt(401);
    }
};

$app->get('/customer/:key', $authKey, function() use ($app)
{
    $sql = "select * FROM tbl_customer";

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'AreaPrast';

    try {
        $db   = getConnection();
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
    
        $response->status(200);
        $response->body(json_encode(array('customer' => $data)));
        //echo '{"data": '.json_encode($data).'}';
    }
    catch(PDOException $e) {
        $response->status(500);
        $response->body(json_encode(array('reasons' => $e->getMessage())));
        //echo '{"error":{"text":'.$e->getMessage().'}}';
    }
});

$app->get('/customer/:key/:id', $authKey, function($key, $id) use ($app) {
    try {
        $sql  = "select * FROM tbl_customer where id_customer = '".$id."'";
        $db   = getConnection();
        $stmt = $db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_OBJ);

        $db   = null;
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($data);

    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->get('/customer/:key/:name', $authKey, function($key, $name) use ($app) {
    try {
        $sql  = "select * FROM tbl_customer where nama_customer = '".$name."'";
        $db   = getConnection();
        $stmt = $db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_OBJ);

        $db   = null;
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($data);

    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

// POST route  ---->>>>>>> Untuk INSERT atau memasukkan data (ex: ke dalam tabel pada database)
/*$app->post(
    '/post',
    function () {
        echo 'This is a POST route';
    }
);*/

$app->post('/customer/:key/', $authKey, function() use ($app) {

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'AreaPrast';

    try {
        $request = $app->request();
        $input   = json_decode($request->getBody());
        $sql     = "INSERT INTO tbl_customer (nama_customer, alamat, telepon, tempat_lahir, tgl_lahir) VALUES (:nama_customer, :alamat, :telepon, :tempat_lahir, :tgl_lahir)";

        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("nama_customer", $input->nama_customer);
        $stmt->bindParam("alamat", $input->alamat);
        $stmt->bindParam("telepon", $input->telepon);
        $stmt->bindParam("tempat_lahir", $input->tempat_lahir);
        $stmt->bindParam("tgl_lahir", $input->tgl_lahir);

        $stmt->execute();
        $input->id_customer = $db->lastInsertId();
        $db   = null;

        $response->status(200);
        $respose->body(json_encode($input));

    } catch (Exception $e) {
        $response()->status(400);
        $response()->body(json_encode(array('reasons' => 'All fields are required.')));
    }
});

// PUT route  ---->>>>>>> Untuk UPDATE atau merubah data yang sudah ada pada tabel database
/*$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);*/

$app->put('/customer/:key/:id/', $authKey, function($key, $id) use ($app) {

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'AreaPrast';

    try {
        $request = $app->request();
        $input   = json_decode($request->getBody());
        $sql     = "UPDATE tbl_customer set nama_customer=:nama_customer, alamat=:alamat, telepon=:telepon, tempat_lahir=:tempat_lahir, tgl_lahir=:tgl_lahir where id_customer='".$id."'";

        $db    = getConnection();
        $stmt  = $db->prepare($sql);
        $stmt->bindParam("nama_customer", $input->nama_customer);
        $stmt->bindParam("alamat", $input->alamat);
        $stmt->bindParam("telepon", $input->telepon);
        $stmt->bindParam("tempat_lahir", $input->tempat_lahir);
        $stmt->bindParam("tgl_lahir", $input->tgl_lahir);

        $stmt->execute();
        $db  = null;

        $response->status(200);
        $respose->body(json_encode($input));

    } catch (Exception $e) {
        $response()->status(400);
        $response()->body(json_encode(array('reasons' => 'All fields are required.')));
    }
});

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route  ---->>>>>>> Untuk DELETE atau menghapus data pada tabel database
/*$app->delete(
    '/delete',
    function () {
        echo 'This is a DELETE route';
    }
);*/

$app->delete('/customer/:key/:id/', $authKey, function($key,$id) use ($app) {

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'AreaPrast';

    try {
        $sql  = "DELETE FROM tbl_customer WHERE id_customer='".$id."'";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $db   = null;

        $response->status(410);
        $response->body(json_encode(array('reasons' => 'Gone')));

    } catch (Exception $e) {
        $response()->status(500);
        $response()->body(json_encode(array('reasons' => 'Something error.')));
    }
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
