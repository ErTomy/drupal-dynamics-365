<?php

namespace Drupal\module_dynamics\Form;

use Drupal\module_dynamics\Form\CrmAuth;
use Drupal\module_dynamics\Form\CrmAuthenticationHeader;
use Drupal\module_dynamics\Form\CrmExecuteSoap;

class Crm
{
    private $url = "https://URL_SERVICOS_WEB";
    private $username = "USUARIO";
    private $password = "CONTRASEÑA";

    private $authHeader = null;

    function __construct() {
        $crmAuth = new CrmAuth ();
        $this->authHeader = $crmAuth->GetHeaderOnline ( $this->username, $this->password, $this->url);

        $log  = "---------------------------------------------------------------------------------------------".PHP_EOL.
        "Nueva petición: " . $_SERVER['REQUEST_URI'] . ' - ' . date("d/m/Y, g:i a").PHP_EOL.
        $this->log($log);
    }


    public function registrar($parametros)
    {
        $campos = [
                    'firstname'=>['type'=>'string', 'value'=>$parametros['nombre']],
                    'apellido1'=>['type'=>'string', 'value'=>$parametros['apellidos']],
                    'emailaddress1'=>['type'=>'string', 'value'=>$parametros['email']]
                  ];


        $this->log("Parametros de la llamada: ".print_r($parametros, TRUE));

        // comprobamos si existe un lead con ese email
        $emails = $this->check('lead', 'emailaddress1', $parametros['email']);


        if(count($emails) == 0){ // no existe, creamos el lead
            $leadId = $this->create('lead', $campos);
        }


    }




    /*
      función usada para crear registros devilviendo el Id insertado.
      En caso de producirse un error devuelve el literal "error" y guarda en la carpeta logs el XML resultante de la petición.
    */
    private function create($entidad, $campos){
        $this->log(PHP_EOL."Crear entidad $entidad con parametros: ".print_r($campos, TRUE));
    		$xml = '<s:Body>
          <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
             <request xmlns:a="http://schemas.microsoft.com/xrm/2011/Contracts" i:type="a:CreateRequest">
                <a:Parameters xmlns:b="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                   <a:KeyValuePairOfstringanyType>
                      <b:key>Target</b:key>
                      <b:value i:type="a:Entity">
                         <a:Attributes>';
                            foreach ($campos as $key => $value) {
                                switch ($value['type']) {
                                  case 'string':
                                      $xml .= '<a:KeyValuePairOfstringanyType><b:key>'.$key.'</b:key>
                                                 <b:value xmlns:c="http://www.w3.org/2001/XMLSchema" i:type="c:string">'.$value['value'].'</b:value>
                                              </a:KeyValuePairOfstringanyType>';
                                  break;
                                  case 'datetime':
                                      $xml .= '<a:KeyValuePairOfstringanyType><b:key>'.$key.'</b:key>
                                                 <b:value xmlns:d="http://www.w3.org/2001/XMLSchema" i:type="d:dateTime">'.$value['value'].'</b:value>
                                              </a:KeyValuePairOfstringanyType>';
                                  break;
                                  case 'enum':
                                      $xml .= '<a:KeyValuePairOfstringanyType><b:key>'.$key.'</b:key>
                                                   <b:value i:type="a:OptionSetValue"><a:Value>'.$value['value'].'</a:Value></b:value>
                                                </a:KeyValuePairOfstringanyType>';
                                  break;
                                  case 'ref':
                                      $xml .= '<a:KeyValuePairOfstringanyType><b:key>'.$key.'</b:key>
                                                <b:value i:type="a:EntityReference"><a:Id>'.$value['id'].'</a:Id><a:LogicalName>'.$value['entity'].'</a:LogicalName><a:Name i:nil="true" /></b:value>
                                              </a:KeyValuePairOfstringanyType>';
                                  break;
                                }
                            }
          $xml .= '               </a:Attributes>
                         <a:EntityState i:nil="true" />
                         <a:FormattedValues />
                         <a:Id>00000000-0000-0000-0000-000000000000</a:Id>
                         <a:LogicalName>'.$entidad.'</a:LogicalName>
                         <a:RelatedEntities />
                      </b:value>
                   </a:KeyValuePairOfstringanyType>
                </a:Parameters>
                <a:RequestId i:nil="true" />
                <a:RequestName>Create</a:RequestName>
             </request>
          </Execute>
       </s:Body>';

    	 	$executeSoap = new CrmExecuteSoap ();
    	 	$response = $executeSoap->ExecuteSOAPRequest ( $this->authHeader, $xml, $this->url );
        $responsedom = new \DomDocument ();
    	 	$responsedom->loadXML ( $response );
        $values = $responsedom->getElementsbyTagName ( "KeyValuePairOfstringanyType" );
        foreach ( $values as $value ) {
      		if ($value->firstChild->textContent == "id") {
            $this->log('Creado con Id:' . $value->lastChild->textContent);
      			return $value->lastChild->textContent;
      		}
      	}

        $this->logfile($response);
        return 'error';
    }


    /*
      función encargada de buscar registros con los valores indicados, devolviendo un array de registros encontrados
    */

    private function check($entidad, $campo, $valor) {
      $this->log("comprobar en entidad $entidad el $campo = $valor");
    	$xml = '<s:Body>
          <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
             <request xmlns:a="http://schemas.microsoft.com/xrm/2011/Contracts" i:type="a:RetrieveRequest">
                <a:Parameters xmlns:b="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                   <a:KeyValuePairOfstringanyType>
                      <b:key>Query</b:key>
                      <b:value i:type="a:QueryExpression">
                         <a:ColumnSet>
                            <a:AllColumns>false</a:AllColumns>
                            <a:Columns xmlns:c="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
                               <c:string>'.$entidad.'id</c:string>
                            </a:Columns>
                         </a:ColumnSet>
                         <a:Criteria>
                            <a:Conditions>
                               <a:ConditionExpression>
                                  <a:AttributeName>'.$campo.'</a:AttributeName>
                                  <a:Operator>Equal</a:Operator>
                                  <a:Values xmlns:c="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
                                     <c:anyType xmlns:d="http://www.w3.org/2001/XMLSchema" i:type="d:string">'.$valor.'</c:anyType>
                                  </a:Values>
                               </a:ConditionExpression>
                            </a:Conditions>
                         </a:Criteria>
                         <a:Distinct>false</a:Distinct>
                         <a:EntityName>'.$entidad.'</a:EntityName>
                         <a:LinkEntities />
                         <a:Orders />
                         <a:PageInfo>
                            <a:Count>0</a:Count>
                            <a:PageNumber>0</a:PageNumber>
                            <a:PagingCookie i:nil="true" />
                            <a:ReturnTotalRecordCount>false</a:ReturnTotalRecordCount>
                         </a:PageInfo>
                         <a:NoLock>false</a:NoLock>
                      </b:value>
                   </a:KeyValuePairOfstringanyType>
                </a:Parameters>
                <a:RequestId i:nil="true" />
                <a:RequestName>RetrieveMultiple</a:RequestName>
             </request>
          </Execute>
       </s:Body>';

    	$executeSoap = new CrmExecuteSoap ();
    	$response = $executeSoap->ExecuteSOAPRequest ( $this->authHeader, $xml, $this->url );

    	$responsedom = new \DomDocument ();
    	$responsedom->loadXML ( $response );

    	$values = $responsedom->getElementsbyTagName ( "KeyValuePairOfstringanyType" );

    	$coincidencias = [];
    	foreach ( $values as $value ) {
    		if ($value->firstChild->textContent == $entidad ."id") {
    			$coincidencias[] = $value->lastChild->textContent;
    		}
    	}

      $this->log("Coincidencias encontradas: ". count($coincidencias));
    	return $coincidencias;

    }




    /*
      función encargada de guardar un log con cada paso y petición realizada
    */
    private function log($log)
    {
        file_put_contents(__DIR__.'/logs/log_'.date("Y-m-d").'.txt', $log . PHP_EOL, FILE_APPEND);
    }

    /*
      función encargada de guardar el XML resultante en caso de devolver un error la petición de crear
    */
    private function logfile($xml)
    {
        $file_error = uniqid() . '.xml';
        $this->log('Se ha producido un error, revisar fichero ' . $file_error);
        file_put_contents(__DIR__.'/logs/'.$file_error, $xml);
    }

}
