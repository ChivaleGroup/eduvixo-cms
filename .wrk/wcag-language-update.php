<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$copy=[
'en'=>['accessibility'=>'Accessibility','text_size'=>'Text size','text_size_help'=>'Adjust text from 100% to 200%.','decrease_text'=>'Decrease text size','increase_text'=>'Increase text size','reset'=>'Reset','close_accessibility'=>'Close accessibility settings'],
'de'=>['accessibility'=>'Barrierefreiheit','text_size'=>'Textgröße','text_size_help'=>'Textgröße von 100 % bis 200 % anpassen.','decrease_text'=>'Text verkleinern','increase_text'=>'Text vergrößern','reset'=>'Zurücksetzen','close_accessibility'=>'Einstellungen zur Barrierefreiheit schließen'],
'pl'=>['accessibility'=>'Dostępność','text_size'=>'Rozmiar tekstu','text_size_help'=>'Dostosuj rozmiar tekstu od 100% do 200%.','decrease_text'=>'Zmniejsz rozmiar tekstu','increase_text'=>'Zwiększ rozmiar tekstu','reset'=>'Resetuj','close_accessibility'=>'Zamknij ustawienia dostępności'],
'zh'=>['accessibility'=>'无障碍设置','text_size'=>'文字大小','text_size_help'=>'将文字大小调整为 100% 至 200%。','decrease_text'=>'减小文字','increase_text'=>'放大文字','reset'=>'重置','close_accessibility'=>'关闭无障碍设置'],
'vi'=>['accessibility'=>'Khả năng tiếp cận','text_size'=>'Cỡ chữ','text_size_help'=>'Điều chỉnh cỡ chữ từ 100% đến 200%.','decrease_text'=>'Giảm cỡ chữ','increase_text'=>'Tăng cỡ chữ','reset'=>'Đặt lại','close_accessibility'=>'Đóng cài đặt khả năng tiếp cận'],
'th'=>['accessibility'=>'การช่วยการเข้าถึง','text_size'=>'ขนาดตัวอักษร','text_size_help'=>'ปรับขนาดตัวอักษรตั้งแต่ 100% ถึง 200%','decrease_text'=>'ลดขนาดตัวอักษร','increase_text'=>'เพิ่มขนาดตัวอักษร','reset'=>'รีเซ็ต','close_accessibility'=>'ปิดการตั้งค่าการช่วยการเข้าถึง'],
'lo'=>['accessibility'=>'ການເຂົ້າເຖິງ','text_size'=>'ຂະໜາດຕົວອັກສອນ','text_size_help'=>'ປັບຂະໜາດຕົວອັກສອນຈາກ 100% ຫາ 200%.','decrease_text'=>'ຫຼຸດຂະໜາດຕົວອັກສອນ','increase_text'=>'ເພີ່ມຂະໜາດຕົວອັກສອນ','reset'=>'ຣີເຊັດ','close_accessibility'=>'ປິດການຕັ້ງຄ່າການເຂົ້າເຖິງ'],
];
foreach($copy as$locale=>$values){$path=$root.'/lang/'.$locale.'.json';$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$data['a11y']=array_replace((array)($data['a11y']??[]),$values);$json=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);if(file_put_contents($path,$json."\n",LOCK_EX)===false)throw new RuntimeException('Cannot update '.$path);}
echo "Accessibility translations updated.\n";
