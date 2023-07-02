<?php 

require_once 'baglan.php';
require 'PHPExcel/PHPExcel.php';

if(isset($_POST['yolla'])){
    $isim = $_FILES['excelFile']['name'];
    $tmp_isim = $_FILES['excelFile']['tmp_name'];
    $tip = $_FILES['excelFile']['type'];

    if($isim && $tmp_isim && $tip){

        $uzantilar = array(
            'application/xls',
            'application/vnd.ms-excel',
            'application/xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        );

        if(in_array($tip,$uzantilar)){

            $excel = PHPExcel_IOFactory::load($tmp_isim);

            foreach($excel->getWorksheetIterator() as $row) {

                $degisken = $row->getHighestRow();

                for($i = 0; $i < $degisken; $i++){

                    $urunadi = $row->getCellByColumnAndRow(0,$i)->getValue();
                    $urunresim = $row->getCellByColumnAndRow(1,$i)->getValue();
                    $urunkodu = $row->getCellByColumnAndRow(2,$i)->getValue();
                    $urunaciklama = $row->getCellByColumnAndRow(3,$i)->getValue();

                    $resimisim = uniqid();
                    $link = $urunresim;
                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $content = @file_get_contents($link, false, stream_context_create($arrContextOptions));
                    $file = "resimler/".$resimisim.".png";
                    $fp = fopen($file, "w");
                    fwrite($fp, $content);
                    fclose($fp);

                    

                    $varmi = $db->prepare("SELECT * FROM urunler WHERE urunkodu=:k");
                    $varmi->execute([':k' => $urunkodu]);
                    if($varmi->rowCount()){

                        $guncelle = $db->prepare("UPDATE urunler SET
                            urunadi =:adi,
                            urunresim=:res,
                            urunaciklamasi=:aciklama WHERE urunkodu=:k
                        ");

                        $guncelle->execute([
                            ':adi' => $urunadi,
                            ':res' => $resimisim.".png",
                            ':aciklama' => $urunaciklama,
                            ':k' => $urunkodu
                        ]);

                    }else{

                        $ekle = $db->prepare("INSERT INTO urunler SET
                            urunadi =:adi,
                            urunresim=:res,
                            urunkodu =:kod,
                            urunaciklamasi=:aciklama
                        ");

                        $ekle->execute([
                            ':adi' => $urunadi,
                            ':res' => $resimisim.".png",
                            ':kod' => $urunkodu,
                            ':aciklama' => $urunaciklama
                        ]);
                        
                    }
                   
                }

            }

            
            if($ekle->rowCount()){
                echo 'ürünler eklendi<br>';
            }else{
                echo 'hata oluştu';
            }

            if($guncelle->rowCount()){
                echo 'ürünler güncellendi<br>';
            }else{
                echo 'hata oluştu';
            }

        
        }else{
            echo "Uzantı uyuşmadı";
        }

    }

}

?>