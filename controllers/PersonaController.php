<?php

namespace app\controllers;

use Yii;
use app\models\mant\Persona;
use app\models\mant\Informante;
use app\models\search\PersonaB;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\base\UserException;

/**
* AVISO IMPORTANTE: Debido a que no estoy usando los botones de submit de ActiveForm sino que simples botones y
* enviando el formulario por medio de JS los valores que se tendrian que guardar como nulos se pasan como candenas vacias
* esto debido a que las solicitudes POST y GET no admiten null.
*/
class PersonaController extends Controller
{
  public function behaviors()
  {
    return [
      'verbs' => [
        'class' => VerbFilter::className(),
        'actions' => [
          'delete' => ['post'],
        ],
      ],
    ];
  }

  /**
  * Lists all Persona models.
  * @return mixed
  */
  public function actionIndex()
  {
    $searchModel = new PersonaB();
    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    return $this->render('index', [
      'searchModel' => $searchModel,
      'dataProvider' => $dataProvider,
      ]);
    }

    /**
    * Displays a single Persona model.
    * @param integer $id
    * @return mixed
    */
    public function actionView($id)
    {
      return $this->render('view', [
        'model' => $this->findModel($id),
        ]);
      }

      /**
      * Creates a new Persona model.
      * If creation is successful, the browser will be redirected to the 'view' page.
      * @return mixed
      */
      public function actionCreate()
      {
        $model = new Persona();
        $conexion = \Yii::$app->db;
        $transaccion = $conexion->beginTransaction();
        if ($model->load(Yii::$app->request->post())) {
          require_once('../auxiliar/Auxiliar.php');
          if($model->validate()){
            foreach ($model->attributes as $llave => $elemento) {
              if($model[$llave] == ''){
                $model[$llave] = null;
              }
            }
            try{
              $model->fecha_nacimiento = fechaMySQL($model->fecha_nacimiento);
              $model->estado = 'Activo';
              if($model->save()){
                if($_POST['informante']=='Si'){
                  $ulid = $conexion->getLastInsertID();
                  $informanteModel = new Informante();
                  $informanteModel->nombre = $model->nombre.' '.$model->apellido;
                  $informanteModel->genero = $model->genero;
                  $informanteModel->cod_persona = $ulid;
                  //Como si es informante guardo la firma, el directorio en linux debe tener permisos 777
                  define("UPLOAD_DIR", Yii::$app->basePath.'/firmas/'.strtolower($informanteModel->genero).'/');
                  if (!empty($_FILES["firma"])) {
                      $myFile = $_FILES["firma"];
                      if ($myFile["error"] !== UPLOAD_ERR_OK) {
                          throw new UserException('Ha ocurrido un error: '.$myFile["error"].', no se pudo subir el archivo');
                      }
                      $ext = end((explode(".", $myFile['name'])));

                      // Generar nombre
                      $nombrec = Yii::$app->security->generateRandomString().'.'.$ext;

                      // No sobre escribir un archivo existente
                      $path = UPLOAD_DIR.$nombrec;
                      while (file_exists($path)) {
                        $nombrec = Yii::$app->security->generateRandomString().'.'.$ext;
                        $path = UPLOAD_DIR.$nombrec;
                      }

                      $informanteModel->firma = $nombrec;

                      if(!empty($model->dui)){
                        $informanteModel->tipo_documento = 'Documento Único de Identidad';
                        $informanteModel->numero_documento = $model->dui;
                      }else{
                        $informanteModel->tipo_documento = 'Carnet de Minoridad';
                        $informanteModel->numero_documento = $model->carnet_minoridad;
                      }

                      if($informanteModel->save()){
                        // Guardar el archivo de forma permanente
                        $success = move_uploaded_file($myFile["tmp_name"],
                            $path);
                        if (!$success) {
                            throw new UserException('No se pudo guardar el archivo, intente de nuevo');
                        }
                        // Darle los permisos adecuados al nuevo archivo
                        chmod($path, 0644);
                      }else{
                        throw new UserException('No se pudo guardar el registro de informante, intente nuevamente');
                      }
                  }else{
                    throw new UserException('No se especifico el archivo de firma');
                  }
                }
                $transaccion->commit();
                return $this->redirect(['view', 'id' => $model->codigo]);
              }else{
                throw new UserException('No se pudo guardar el registro de persona, intente nuevamente');
              }
            }catch(UserException $err){
              $transaccion->rollback();
              Yii::$app->session->setFlash('error', $err->getMessage());
              return $this->redirect(['create']);
            }
          }
        }
        return $this->render('create', ['model' => $model]);
      }
      /**
      * Updates an existing Persona model.
      * If update is successful, the browser will be redirected to the 'view' page.
      * @param integer $id
      * @return mixed
      */
      public function actionUpdate($id)
      {
        $model = new Persona();
        $conexion = \Yii::$app->db;
        $transaccion = $conexion->beginTransaction();
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post())) {
          require_once('../auxiliar/Auxiliar.php');
          if($model->validate()){
            foreach ($model->attributes as $llave => $elemento) {
              if($model[$llave] == ''){
                $model[$llave] = null;
              }
            }
            $model->fecha_nacimiento = fechaMySQL($model->fecha_nacimiento);
            try{
              $informanteModel = Informante::find()->where('cod_persona = :valor',[':valor'=>$model->codigo])->one();
              if($model->save()){
                if(!empty($informanteModel)){
                  //Si tiene un informante asociada, habra que actualizarlo también
                  $informanteModel->nombre = $model->nombre.' '.$model->apellido;
                  $informanteModel->genero = $model->genero;
                  if(!$informanteModel->save()){
                    throw new UserException('No se pudo actualizar el registro de informante asociado, intente nuevamente');
                  }
                }
                $transaccion->commit();
                return $this->redirect(['view', 'id' => $model->codigo]);
              }else{
                throw new UserException('No se pudo actualizar el registro de persona, intente nuevamente');
              }
            }catch(UserException $err){
              $transaccion->rollback();
              Yii::$app->session->setFlash('error', $err->getMessage());
              return $this->redirect(['update']);
            }
          }
        }
          return $this->render('update', ['model' => $model,]);
        }

        /**
        * Deletes an existing Persona model.
        * If deletion is successful, the browser will be redirected to the 'index' page.
        * @param integer $id
        * @return mixed
        */
        public function actionDelete($id)
        {
          $this->findModel($id)->delete();
          return $this->redirect(['index']);
        }

        /**
        * Finds the Persona model based on its primary key value.
        * If the model is not found, a 404 HTTP Exception will be thrown.
        * @param integer $id
        * @return Persona the loaded model
        * @throws NotFoundHttpException if the model cannot be found
        */
        protected function findModel($id)
        {
          if (($model = Persona::findOne($id)) !== null) {
            return $model;
          } else {
            throw new NotFoundHttpException('La página solicitida no existe');
          }
        }
      }
