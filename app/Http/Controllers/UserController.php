<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;
use App\Models\ThuVienHinh;
use App\Models\Laptop;
use App\Models\PhuKien;
use App\Models\HangSanXuat;
use App\Models\QuaTang;
use App\Models\NguoiDung;
use App\Models\PhieuXuat;
use App\Models\ChiTietPhieuXuat;
use App\Models\MaGiamGia;
use App\Models\LoiPhanHoi;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    private $sanPham;
    private $laptop;
    private $phuKien;
    private $thuVienHinh;
    private $hangSanXuat;
    private $quaTang;
    private $nguoiDung;
    private $phieuXuat;
    private $chiTietPhieuXuat;
    private $maGiamGia;
    private $loiPhanHoi;
    public function __construct()
    {
        $this->sanPham = new SanPham();
        $this->laptop = new Laptop();
        $this->phuKien = new PhuKien();
        $this->thuVienHinh = new ThuVienHinh();
        $this->hangSanXuat = new HangSanXuat();
        $this->quaTang = new QuaTang();
        $this->nguoiDung = new NguoiDung();
        $this->phieuXuat = new PhieuXuat();
        $this->chiTietPhieuXuat = new ChiTietPhieuXuat();
        $this->maGiamGia = new MaGiamGia();
        $this->loiPhanHoi = new LoiPhanHoi();
    }
    public function trangchu()
    {
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachSanPhamMoiRaMat = $this->sanPham->layDanhSachSanPhamTheoBoLoc([], NULL, 'moinhat');
        $danhSachSanPhamBanChay = $this->sanPham->layDanhSachSanPhamTheoBoLoc([], NULL, 'banchaynhat');
        $danhSachSanPhamUuDai = $this->sanPham->layDanhSachSanPhamTheoBoLoc([], NULL, 'uudainhat');
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachLaptop = $this->laptop->layDanhSachLaptop();
        $danhSachSanPhamLaLaptop = [];
        $danhSachSanPhamLaPhuKien = [];
        $danhSachLaptopSinhVien = [];
        $danhSachLaptopDoHoa = [];
        $danhSachLaptopGaming = [];
        foreach ($danhSachSanPham as $sanpham) {
            if ($sanpham->loaisanpham == 1) { // la phu kien
                $danhSachSanPhamLaPhuKien = array_merge($danhSachSanPhamLaPhuKien, [$sanpham]);
            }
            if ($sanpham->loaisanpham == 0) { // la laptop
                $danhSachSanPhamLaLaptop = array_merge($danhSachSanPhamLaLaptop, [$sanpham]);
                $thongTinLaptop = $this->laptop->timLaptopTheoMa($sanpham->malaptop);
                if ($thongTinLaptop->nhucau == 'Sinh Vi??n') { // la laptop nhu cau la sinh vien
                    $danhSachLaptopSinhVien = array_merge($danhSachLaptopSinhVien, [$sanpham]);
                } elseif ($thongTinLaptop->nhucau == '????? H???a') { // la laptop nhu cau la do hoa
                    $danhSachLaptopDoHoa = array_merge($danhSachLaptopDoHoa, [$sanpham]);
                } elseif ($thongTinLaptop->nhucau == 'Gaming') { // la laptop nhu cau la gaming
                    $danhSachLaptopGaming = array_merge($danhSachLaptopGaming, [$sanpham]);
                }
            }
        }
        return view('user.trangchu', compact(
            'danhSachSanPham',
            'danhSachSanPhamMoiRaMat',
            'danhSachSanPhamBanChay',
            'danhSachSanPhamUuDai',
            'danhSachThuVienHinh',
            'danhSachHangSanXuat',
            'danhSachLaptop',
            'danhSachLaptopSinhVien',
            'danhSachLaptopDoHoa',
            'danhSachLaptopGaming',
            'danhSachSanPhamLaLaptop',
            'danhSachSanPhamLaPhuKien'
        ));
    }
    public function chitietsp(Request $request)
    {
        $request->validate(['masp' => 'required|integer|exists:sanpham,masanpham']);
        $sanPhamXem = $this->sanPham->timSanPhamTheoMa($request->masp);
        $cauHinh = NULL;
        $thongTinPhuKien = NULL;
        if ($sanPhamXem->loaisanpham == 0 && !empty($sanPhamXem->malaptop)) { //la laptop
            $cauHinh = $this->laptop->timLaptopTheoMa($sanPhamXem->malaptop);
        } elseif ($sanPhamXem->loaisanpham == 1 && !empty($sanPhamXem->maphukien)) { //la phu kien
            $thongTinPhuKien = $this->phuKien->timPhuKienTheoMa($sanPhamXem->maphukien);
        }
        $thuVienHinhXem = $this->thuVienHinh->timThuVienHinhTheoMa($sanPhamXem->mathuvienhinh);
        $hangSanXuatXem = $this->hangSanXuat->timHangSanXuatTheoMa($sanPhamXem->mahang);
        $quaTangXem = $this->quaTang->timQuaTangTheoMa($sanPhamXem->maquatang);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachLaptop = $this->laptop->layDanhSachLaptop();
        $danhSachSanPhamTang = [];
        $flag = false;
        foreach ($quaTangXem as $giaTri) {
            if ($flag && !empty($giaTri)) {
                $sanPhamTang = $this->sanPham->timSanPhamTheoMa($giaTri);
                if (!empty($sanPhamTang)) {
                    $danhSachSanPhamTang = array_merge($danhSachSanPhamTang, [$sanPhamTang]);
                }
            }
            if (is_string($giaTri)) $flag = true;
        }
        $danhSachSanPhamTuongTu = [];
        $danhSachLaptopCu = [];
        foreach ($danhSachSanPham as $sanpham) {
            if ($sanpham->loaisanpham == $sanPhamXem->loaisanpham && $sanpham->masanpham != $sanPhamXem->masanpham) {
                $danhSachSanPhamTuongTu = array_merge($danhSachSanPhamTuongTu, [$sanpham]);
            }
            if ($sanpham->loaisanpham == 0 && $sanpham->masanpham != $sanPhamXem->masanpham) {
                $thongTinLaptop = $this->laptop->timLaptopTheoMa($sanpham->malaptop);
                if ($thongTinLaptop->tinhtrang == 1) { // la laptop cu
                    $danhSachLaptopCu = array_merge($danhSachLaptopCu, [$sanpham]);
                }
            }
        }
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.chitietsp', compact(
            'sanPhamXem',
            'cauHinh',
            'thongTinPhuKien',
            'thuVienHinhXem',
            'hangSanXuatXem',
            'danhSachHangSanXuat',
            'danhSachSanPhamTang',
            'danhSachSanPhamTuongTu',
            'danhSachThuVienHinh',
            'danhSachLaptopCu',
            'danhSachLaptop'
        ));
    }
    public function danhsachsp(Request $request)
    {
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachLaptop = $this->laptop->layDanhSachLaptop();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        if (!empty($request->all())) {
            $boLoc = [];
            $Cpu = [];
            $Ram = [];
            $cardDoHoa = [];
            $oCung = [];
            $manHinh = [];
            $nhuCau = [];
            $tinhTrang = [];
            $mucGia = [];
            $tuKhoa = NULL;
            $sapXep = NULL;
            if (isset($request->loaisp) && $request->loaisp == 0) {
                $boLoc[] = ['sanpham.loaisanpham', '=', 0];
            }
            if (!empty($request->hangsx)) {
                $hangsx = explode(',', $request->hangsx);
                $boLoc[] = ['sanpham.mahang', $hangsx];
            }
            if (!empty($request->cpu)) {
                $cpu = explode(',', $request->cpu);
                if (in_array('intelcorei3', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Intel Core i3%'];
                }
                if (in_array('intelcorei5', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Intel Core i5%'];
                }
                if (in_array('intelcorei7', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Intel Core i7%'];
                }
                if (in_array('amdryzen3', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Amd Ryzen 3%'];
                }
                if (in_array('amdryzen5', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Amd Ryzen 5%'];
                }
                if (in_array('amdryzen7', $cpu)) {
                    $Cpu[] = ['laptop.cpu', 'like', '%Amd Ryzen 7%'];
                }
            }
            if (!empty($request->ram)) {
                $ram = explode(',', $request->ram);
                if (in_array(4, $ram)) {
                    $Ram[] = ['laptop.ram', '=', 4];
                }
                if (in_array(8, $ram)) {
                    $Ram[] = ['laptop.ram', '=', 8];
                }
                if (in_array(16, $ram)) {
                    $Ram[] = ['laptop.ram', '=', 16];
                }
            }
            if (!empty($request->carddohoa)) {
                $carddohoa = explode(',', $request->carddohoa);
                if (in_array('onboard', $carddohoa)) {
                    $cardDoHoa[] = ['laptop.carddohoa', '=', 0];
                }
                if (in_array('nvidia', $carddohoa)) {
                    $cardDoHoa[] = ['laptop.carddohoa', '=', 1];
                }
                if (in_array('amd', $carddohoa)) {
                    $cardDoHoa[] = ['laptop.carddohoa', '=', 2];
                }
            }
            if (!empty($request->ocung)) {
                $ocung = explode(',', $request->ocung);
                if (in_array(128, $ocung)) {
                    $oCung[] = ['laptop.ocung', '=', 128];
                }
                if (in_array(256, $ocung)) {
                    $oCung[] = ['laptop.ocung', '=', 256];
                }
                if (in_array(512, $ocung)) {
                    $oCung[] = ['laptop.ocung', '=', 512];
                }
            }
            if (!empty($request->manhinh)) {
                $manhinh = explode(',', $request->manhinh);
                if (in_array(13, $manhinh)) {
                    $manHinh[] = [13, 13.9];
                }
                if (in_array(14, $manhinh)) {
                    $manHinh[] = [14, 14.9];
                }
                if (in_array(15, $manhinh)) {
                    $manHinh[] = [15, 16];
                }
            }
            if (!empty($request->nhucau)) {
                $nhucau = explode(',', $request->nhucau);
                if (in_array('sinhvien', $nhucau)) {
                    $nhuCau[] = ['laptop.nhucau', '=', 'Sinh Vi??n'];
                }
                if (in_array('dohoa', $nhucau)) {
                    $nhuCau[] = ['laptop.nhucau', '=', '????? H???a'];
                }
                if (in_array('gaming', $nhucau)) {
                    $nhuCau[] = ['laptop.nhucau', '=', 'Gaming'];
                }
            }
            if (!empty($request->tinhtrang)) {
                $tinhtrang = explode(',', $request->tinhtrang);
                if (in_array('moi', $tinhtrang)) {
                    $tinhTrang[] = ['laptop.tinhtrang', '=', 0];
                }
                if (in_array('cu', $tinhtrang)) {
                    $tinhTrang[] = ['laptop.tinhtrang', '=', 1];
                }
            }
            if (!empty($request->mucgia)) {
                $mucgia = explode(',', $request->mucgia);
                if (in_array('duoi10', $mucgia)) {
                    $mucGia[] = [0, 10000000];
                }
                if (in_array('1015', $mucgia)) {
                    $mucGia[] = [10000000, 15000000];
                }
                if (in_array('1520', $mucgia)) {
                    $mucGia[] = [15000000, 20000000];
                }
                if (in_array('tren20', $mucgia)) {
                    $mucGia[] = [20000000, 2000000000];
                }
            }
            if (!empty($request->sapxep)) {
                $sapXep = $request->sapxep;
            }
            // $boLoc = [], $tuKhoa = NULL, $sapXep = NULL, $mucGia = [], $tinhTrang = [], $nhuCau = [], $manHinh = [], $oCung = [], $cardDoHoa = [], $Ram = [], $Cpu = []
            $danhSachSanPham = $this->sanPham->layDanhSachSanPhamTheoBoLoc($boLoc, $tuKhoa, $sapXep, $mucGia, $tinhTrang, $nhuCau, $manHinh, $oCung, $cardDoHoa, $Ram, $Cpu);
        }
        return view('user.danhsachsp', compact(
            'danhSachSanPham',
            'danhSachLaptop',
            'danhSachThuVienHinh',
            'danhSachHangSanXuat'
        ));
    }
    public function giohang()
    {
        if (empty(session('gioHang'))) return redirect()->route('/')->with('thongbao', 'Gi??? h??ng ch??a c?? s???n ph???m!');
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.giohang', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function xulygiohang(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "??p d???ng") { // *******************************************************************************************ap dung ma giam gia
            $rules = [
                'maGiamGia' => 'required|string|max:50|min:3|exists:giamgia,magiamgia'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'unique' => ':attribute ???? ???????c ??p d???ng cho phi???u xu???t',
                'string' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maGiamGia' => 'M?? gi???m gi??'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinMaGiamGia = $this->maGiamGia->timMaGiamGiaTheoMa($request->maGiamGia); //tim ma giam gia
            if (strtotime($thongTinMaGiamGia->ngayketthuc) - strtotime(date('Y-m-d')) >= 0) { //neu con han su dung
                session(['maGiamGia' => $thongTinMaGiamGia]);
                return back()->with('thongbao', '??p d???ng m?? gi???m gi?? th??nh c??ng!');
            } else {
                return back()->with('thongbao', 'M?? gi???m gi?? ???? h???t h???n s??? d???ng');
            }
            return back()->with('thongbao', '??p d???ng m?? gi???m gi?? th???t b???i!');
        }
        if ($request->thaoTac == "x??a gi??? h??ng") { // *******************************************************************************************xoa gio hang
            $rules = [
                'maSanPhamMuaXoa' => 'required|integer|exists:sanpham,masanpham'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'integer' => ':attribute ???? nh???p sai',
                'exists' => ':attribute kh??ng t???n t???i'
            ];
            $attributes = [
                'maSanPhamMuaXoa' => 'M?? s???n ph???m c???n x??a'
            ];
            $request->validate($rules, $messages, $attributes);
            $gioHang = [];
            if (!empty(session('gioHang'))) {
                foreach (session('gioHang') as $ctgh) { // duyet qua gio hang cu
                    if ($request->maSanPhamMuaXoa != $ctgh['masanpham']) { // neu chi tiet gio hang khac voi san pham can xoa trong gio hang
                        $gioHang = array_merge($gioHang, [$ctgh]); // thi them chi tiet gio hang do vao gio
                    } // con neu chi tiet gio hang co ma san pham trung voi san pham can xoa trong gio hang thi khong dc them vao gio hang moi
                }
            }
            session(['gioHang' => $gioHang]); //thay gio hang cu bang gio hang moi
            if (empty(session('gioHang'))) {
                session()->forget('maGiamGia');
                session()->forget('gioHang');
            }
            return back()->with('thongbao', 'X??a s???n ph???m SP' . $request->maSanPhamMuaXoa . ' kh???i gi??? h??ng th??nh c??ng!');
        }
        if ($request->thaoTac == "c???p nh???t") { // *******************************************************************************************sua gio hang
            $rules = [
                'soLuongMuaSua' => 'required|array',
                'soLuongMuaSua.*' => 'required|integer'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'integer' => ':attribute ???? nh???p sai',
                'array' => ':attribute nh???p sai'
            ];
            $attributes = [
                'soLuongMuaSua' => 'S??? l?????ng mua',
                'soLuongMuaSua.*' => 'S??? l?????ng mua'
            ];
            $request->validate($rules, $messages, $attributes);
            $gioHang = [];
            if (!empty(session('gioHang'))) {
                foreach (session('gioHang') as $ctgh) {
                    $soLuongMuaMoi = $request->soLuongMuaSua[$ctgh['masanpham']];
                    if ($soLuongMuaMoi > 0) {
                        $ctgh['soluongmua'] = $soLuongMuaMoi;
                        $gioHang = array_merge($gioHang, [$ctgh]); // neu so luong chinh sua gio hang lon hon 0 thi so luong mua trong gio hang thay bang so luong mua moi vua sua
                    }
                }
            }
            session(['gioHang' => $gioHang]);
            if (empty(session('gioHang'))) {
                session()->forget('maGiamGia');
                session()->forget('gioHang');
            }
            return back()->with('thongbao', 'C???p nh???t gi??? h??ng th??nh c??ng!');
        }
        if ($request->thaoTac == "th??m gi??? h??ng") { // *******************************************************************************************them gio hang
            $rules = [
                'maSanPhamMua' => 'required|integer|exists:sanpham,masanpham',
                'soLuongMua' => 'required|integer'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maSanPhamMua' => 'M?? s???n ph???m',
                'soLuongMua' => 'S??? l?????ng mua'
            ];
            $request->validate($rules, $messages, $attributes);
            $soLuongMua = $request->soLuongMua;
            $thongTinSanPhamMua = $this->sanPham->timSanPhamTheoMa($request->maSanPhamMua); //tim san pham da them vao gio hang
            if (!empty($thongTinSanPhamMua)) { //neu tim thay
                if (($thongTinSanPhamMua->giaban <= 0)) { //san pham chua nhap
                    return back()->with('thongbao', 'Li??n h??? 090.xxx.xnxx (Mr.Ti???n) ????? nh???n ???????c gi?? c??? th??? nh???t!');
                }
            }
            $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPhamMua->mathuvienhinh); //tim hinh san pham da them vao gio hang
            $thongTinQuaTang = $this->quaTang->timQuaTangTheoMa($thongTinSanPhamMua->maquatang); //tim qua tang cua san pham da them vao gio hang
            $danhSachSanPhamTang = [];
            $flag = false;
            foreach ($thongTinQuaTang as $giaTri) {
                if ($flag && !empty($giaTri)) {
                    $sanPhamTang = $this->sanPham->timSanPhamTheoMa($giaTri);
                    if (!empty($sanPhamTang)) {
                        $danhSachSanPhamTang = array_merge($danhSachSanPhamTang, [$sanPhamTang]);
                    }
                }
                if (is_string($giaTri)) $flag = true;
            }
            if (!empty($thongTinSanPhamMua) && !empty($thongTinHinh)) {
                $chiTietGioHang = [
                    'masanpham' => $thongTinSanPhamMua->masanpham,
                    'tensanpham' => $thongTinSanPhamMua->tensanpham,
                    'baohanh' => $thongTinSanPhamMua->baohanh,
                    'giaban' => $thongTinSanPhamMua->giaban,
                    'giakhuyenmai' => $thongTinSanPhamMua->giakhuyenmai,
                    'hinh' => $thongTinHinh->hinh1,
                    'quatang' => $danhSachSanPhamTang,
                    'soluongmua' => $soLuongMua
                ];
                $gioHang = [];
                $flag = false;
                if (!empty(session('gioHang'))) {
                    foreach (session('gioHang') as $ctgh) {
                        if ($ctgh['masanpham'] == $chiTietGioHang['masanpham']) { // tim xem chi tiet gio hang vua them co san trong gio hang chua
                            $ctgh['soluongmua'] += $chiTietGioHang['soluongmua']; // neu co thi tang so luong mua
                            $flag = true; // chi tiet gio hang nay da dc them vao gio hang bien co se duoc bat len de khoi phai them vao lan nua
                        }
                        $gioHang = array_merge($gioHang, [$ctgh]);
                    }
                }
                if (!$flag) { // bien co chua bat thi la san pham nay chua co trong gio hang va them vao thanh chi tiet gio hang moi
                    $gioHang = array_merge($gioHang, [$chiTietGioHang]);
                }
                session(['gioHang' => $gioHang]);
                return back()->with('thongbao', 'Th??m gi??? h??ng th??nh c??ng!');
            }
        }
        return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
    }
    public function baohanh()
    {
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.baohanh', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function tragop(Request $request)
    {
        session()->flush();
        return back();
    }
    public function lienhe()
    {
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.lienhe', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function xulylienhe(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "g???i l???i nh???n") { // *******************************************************************************************gui loi nhan
            $rules = [
                'hoTen' => 'required|string|max:50|min:3',
                'soDienThoai' => 'required|numeric|digits:10',
                'diaChi' => 'required|string|max:255|min:3',
                'noiDung' => 'required|string|max:255|min:3'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???'
            ];
            $attributes = [
                'hoTen' => 'H??? t??n',
                'soDienThoai' => 'S??? ??i???n tho???i',
                'diaChi' => '?????a ch???',
                'noiDung' => 'N???i dung'
            ];
            $request->validate($rules, $messages, $attributes);
            $ngayTao = date("Y-m-d H:i:s");
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoSoDienThoai($request->soDienThoai); //tim nguoi dung da ton tai hay chua
            if (!empty($thongTinNguoiDung)) { //neu tim thay
                if ($thongTinNguoiDung->trangthai == 0) { //neu nguoi dung dang bi khoa
                    return back()->with('thongbao', 'Th??ng tin ng?????i d??ng hi???n ??ang b??? t???m kh??a do h???y qu?? nhi???u ????n!');
                }
                $dataNguoiDung = [
                    $request->hoTen,
                    $thongTinNguoiDung->sodienthoai,
                    $request->diaChi,
                    $thongTinNguoiDung->loainguoidung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    $thongTinNguoiDung->email,
                    $thongTinNguoiDung->password
                ];
                $this->nguoiDung->suaNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung); //sua lai thong tin nguoi dung
            } else {
                $dataNguoiDung = [
                    NULL, //manguoidung tu tang
                    $request->hoTen,
                    $request->soDienThoai,
                    $request->diaChi,
                    1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                    0, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    NULL, //email
                    NULL, //matkhau
                    $ngayTao
                ];
                $this->nguoiDung->themNguoiDung($dataNguoiDung); //them nguoi dung vao database
                $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoNgayTao($ngayTao); //tim nguoi dung vua them
            }
            $dataLoiPhanHoi = [
                $request->noiDung, //noidung,
                0, //trangthai, 0 la chua doc // 1 la da doc
                $thongTinNguoiDung->manguoidung, //manguoidung,
                $ngayTao //ngaytao
            ];
            $this->loiPhanHoi->themLoiPhanHoi($dataLoiPhanHoi); //them loi phan hoi vao database
            return redirect()->route('/')->with('thongbao', 'G???i l???i nh???n th??nh c??ng, s??? c?? nh??n vi??n li??n h??? b???n s???m nh???t c?? th???!');
        }
        return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
    }
    public function dangnhap()
    {
        if (Auth::check()) {
            if (Auth::user()->loainguoidung == 2) {
                return redirect()->route('tongquan');
            }
            return redirect()->route('taikhoan');
        }
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.dangnhap', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function taikhoan()
    {
        if (!Auth::check()) {
            return redirect()->route('dangnhap');
        }
        $danhSachPhieuXuat = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['manguoidung', '=', Auth::user()->manguoidung]]);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachMaGiamGia = $this->maGiamGia->layDanhSachMaGiamGia();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachChiTietPhieuXuat = $this->chiTietPhieuXuat->layDanhSachChiTietPhieuXuat();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.taikhoan', compact(
            'danhSachPhieuXuat',
            'danhSachSanPham',
            'danhSachMaGiamGia',
            'danhSachThuVienHinh',
            'danhSachHangSanXuat',
            'danhSachChiTietPhieuXuat'
        ));
    }
    public function dangxuat()
    {
        if (Auth::check()) {
            Auth::logout();
        }
        return back();
    }
    public function xulytaikhoan(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "?????i th??ng tin") { // *******************************************************************************************doi thong tin giao hang
            $rules = [
                'email' => 'required|email|max:150|min:5|exists:nguoidung,email',
                'hoTen' => 'required|string|max:50|min:3',
                'soDienThoai' => 'required|numeric|digits:10',
                'diaChi' => 'required|string|max:255|min:3'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'exists' => ':attribute kh??ng t???n t???i',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???',
                'email' => ':attribute kh??ng ????ng ?????nh d???ng email'
            ];
            $attributes = [
                'email' => 'Email',
                'hoTen' => 'H??? t??n',
                'soDienThoai' => 'S??? ??i???n tho???i',
                'diaChi' => '?????a ch???'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoSoDienThoai($request->soDienThoai); //tim so dien thoai da ton tai hay chua
            if (!empty($thongTinNguoiDung) && $thongTinNguoiDung->email != $request->email) {
                return back()->with('loidoithongtin', 'S??? ??i???n tho???i ???? t???n t???i.')->with('thongbao', '?????i th??ng tin th???t b???i.');
            }
            if (Auth::check()) {
                if ($request->email == Auth::user()->email) { //email dung voi tai khoan tren database
                    $dataNguoiDung = [
                        $request->hoTen,
                        $request->soDienThoai,
                        $request->diaChi,
                        Auth::user()->loainguoidung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                        Auth::user()->email,
                        Auth::user()->password
                    ];
                    $this->nguoiDung->suaNguoiDung($dataNguoiDung, Auth::user()->manguoidung); //sua lai thong tin nguoi dung
                    return back()->with('thongbao', '?????i th??ng tin th??nh c??ng.');
                }
                return back()->with('loidoithongtin', 'Email kh??ng ch??nh x??c.')->with('thongbao', '?????i th??ng tin th???t b???i.');
            }
            return back()->with('loidoithongtin', 'Th??ng tin ????ng nh???p kh??ng h???p l???.')->with('thongbao', '?????i th??ng tin th???t b???i.');
        }
        if ($request->thaoTac == "?????i m???t kh???u") { // *******************************************************************************************doi mat khau
            $rules = [
                'email' => 'required|email|max:150|min:5|exists:nguoidung,email',
                'matKhauCu' => 'required|string|max:32|min:8',
                'matKhauMoi' => 'required|string|max:32|min:8',
                'nhapLaiMatKhauMoi' => 'required|string|max:32|min:8|same:matKhauMoi'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'exists' => ':attribute kh??ng t???n t???i',
                'email' => ':attribute kh??ng ????ng ?????nh d???ng email',
                'same' => ':attribute kh??ng kh???p v???i m???t kh???u'
            ];
            $attributes = [
                'email' => 'Email',
                'matKhauCu' => 'M???t kh???u c??',
                'matKhauMoi' => 'M???t kh???u m???i',
                'nhapLaiMatKhauMoi' => 'Nh???p l???i m???t kh???u m???i'
            ];
            $request->validate($rules, $messages, $attributes);
            if ($request->matKhauCu == $request->matKhauMoi) {
                return back()->with('loidoimatkhau', 'M???t kh???u c?? v?? m???t kh???u m???i tr??ng nhau.')->with('thongbao', '?????i m???t kh???u th???t b???i.');
            }
            if (Auth::check()) {
                if ($request->email == Auth::user()->email && Hash::check($request->matKhauCu, Auth::user()->password)) { //email va mat khau cu dung voi tai khoan tren database
                    $dataNguoiDung = [
                        Auth::user()->hoten,
                        Auth::user()->sodienthoai,
                        Auth::user()->diachi,
                        Auth::user()->loainguoidung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                        Auth::user()->email,
                        bcrypt($request->matKhauMoi)
                    ];
                    $this->nguoiDung->suaNguoiDung($dataNguoiDung, Auth::user()->manguoidung); //sua lai thong tin nguoi dung
                    return back()->with('thongbao', '?????i m???t kh???u th??nh c??ng.');
                }
                return back()->with('loidoimatkhau', 'M???t kh???u c?? kh??ng ch??nh x??c.')->with('thongbao', '?????i m???t kh???u th???t b???i.');
            }
            return back()->with('loidoimatkhau', 'Th??ng tin ????ng nh???p kh??ng h???p l???.')->with('thongbao', '?????i m???t kh???u th???t b???i.');
        }
        if ($request->thaoTac == "????ng nh???p") { // *******************************************************************************************dang nhap
            $rules = [
                'email' => 'required|email|max:150|min:5|exists:nguoidung,email',
                'matKhau' => 'required|string|max:32|min:8'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'exists' => ':attribute kh??ng t???n t???i',
                'email' => ':attribute kh??ng ????ng ?????nh d???ng email',
            ];
            $attributes = [
                'email' => 'Email',
                'matKhau' => 'M???t kh???u'
            ];
            $request->validate($rules, $messages, $attributes);
            $dataNguoiDung = [
                'email' => $request->email,
                'password' => $request->matKhau
            ];
            if (Auth::attempt($dataNguoiDung)) {
                if (Auth::user()->trangthai == 0) { //neu tai khoan dang bi khoa
                    Auth::logout();
                    return back()->with('loidangnhap', 'T??i kho???n hi???n ??ang b??? kh??a.');
                }
                if (Auth::user()->loainguoidung == 2) { //neu tai khoan la nhan vien
                    return redirect()->route('tongquan')->with('hoTenNhanVien', Auth::user()->hoten);
                }
                return redirect()->back();
            }
            return back()->with('loidangnhap', 'Th??ng tin ????ng nh???p kh??ng h???p l???.');
        }
        if ($request->thaoTac == "????ng k??") {  // *******************************************************************************************dang ky
            $rules = [
                'emailDangKy' => 'required|email|max:150|min:5|unique:nguoidung,email',
                'matKhauDangKy' => 'required|string|max:32|min:8',
                'nhapLaiMatKhauDangKy' => 'required|string|max:32|min:8|same:matKhauDangKy',
                'hoTen' => 'required|string|max:50|min:3',
                'soDienThoai' => 'required|numeric|digits:10',
                'diaChi' => 'required|string|max:255|min:3'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???',
                'unique' => ':attribute ???? t???n t???i',
                'email' => ':attribute kh??ng ????ng ?????nh d???ng email',
                'same' => ':attribute kh??ng kh???p v???i m???t kh???u'
            ];
            $attributes = [
                'emailDangKy' => 'Email',
                'matKhauDangKy' => 'M???t kh???u',
                'nhapLaiMatKhauDangKy' => 'Nh???p l???i m???t kh???u',
                'hoTen' => 'H??? t??n',
                'soDienThoai' => 'S??? ??i???n tho???i',
                'diaChi' => '?????a ch???'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoSoDienThoai($request->soDienThoai); //tim nguoi dung da ton tai hay chua
            if (!empty($thongTinNguoiDung)) { //neu tim thay
                if ($thongTinNguoiDung->trangthai == 0) { //neu nguoi dung dang bi khoa
                    return back()->with('loidangky', 'S??? ??i???n tho???i hi???n ??ang b??? kh??a.');
                }
                if (!empty($thongTinNguoiDung->email)) { //da co tai khoan nen khong the tao tai khoan moi
                    return back()->with('loidangky', 'S??? ??i???n tho???i ???? t???n t???i.');
                } else { //chua co tai khoan thi tao tai khoan
                    $dataNguoiDung = [
                        $request->emailDangKy,
                        bcrypt($request->matKhauDangKy)
                    ];
                    $this->nguoiDung->taoTaiKhoanNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung); //tao tai khoan cho nguoi dung
                }
                $dataNguoiDung = [
                    $request->hoTen,
                    $thongTinNguoiDung->sodienthoai,
                    $request->diaChi,
                    $thongTinNguoiDung->loainguoidung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    $thongTinNguoiDung->email,
                    $thongTinNguoiDung->password

                ];
                $this->nguoiDung->suaNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung); //sua lai thong tin nguoi dung
            } else {
                $ngayTao = date("Y-m-d H:i:s");
                $dataNguoiDung = [
                    NULL, //manguoidung tu tang
                    $request->hoTen,
                    $request->soDienThoai,
                    $request->diaChi,
                    1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                    0, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    $request->emailDangKy,
                    bcrypt($request->matKhauDangKy),
                    $ngayTao
                ];
                $this->nguoiDung->themNguoiDung($dataNguoiDung); //them nguoi dung vao database
            }
            $dataNguoiDung = [
                'email' => $request->emailDangKy,
                'password' => $request->matKhauDangKy
            ];
            if (Auth::attempt($dataNguoiDung)) {
                return redirect()->route('taikhoan');
            }
        }
        return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
    }
    public function thanhtoan(Request $request)
    {
        if (empty(session('gioHang'))) return redirect()->route('giohang');
        if (isset($request->vnp_ResponseCode) && isset($request->vnp_TransactionStatus)) { // sau khi thanh toan vnpay thanh cong
            if ($request->vnp_ResponseCode == "00" && $request->vnp_TransactionStatus == "00") {
                $dataPhieuXuat = json_decode($request->vnp_OrderInfo);
                $dataPhieuXuat[10] += ($request->vnp_Amount / 100); // cong no
                // if (!empty($dataPhieuXuat[5])) {// ma giam gia dc ap dung
                //     $thongTinMaGiamGia = $this->maGiamGia->timMaGiamGiaTheoMa($dataPhieuXuat[5]); //tim ma giam gia
                //     if (!empty($thongTinMaGiamGia)) {
                //         if (strtotime($thongTinMaGiamGia->ngayketthuc) - strtotime(date('Y-m-d')) >= 0) { //neu con han su dung
                //             $dataPhieuXuat[10] -= $thongTinMaGiamGia->sotiengiam;
                //         } else {
                //             return back()->with('thongbao', 'M?? gi???m gi?? ???? h???t h???n s??? d???ng!');
                //         }
                //     } else {
                //         return back()->with('thongbao', 'M?? gi???m gi?? kh??ng t???n t???i!');
                //     }
                // }
                // dd($dataPhieuXuat);
                $this->phieuXuat->themPhieuXuat($dataPhieuXuat); //them phieu xuat vao database
                $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoNgayTao($dataPhieuXuat[11]); //tim phieu xuat vua them
                foreach (session('gioHang') as $ctgh) {
                    $donGia = $ctgh['giaban'];
                    if (!empty($ctgh['giakhuyenmai'])) {
                        $donGia = $ctgh['giakhuyenmai'];
                    }
                    if (!empty($ctgh['quatang'])) { // xem chi tiet gio hang san pham do co qua tang khong neu co qua tang xuat them chi tiet phieu xuat 0 dong
                        foreach ($ctgh['quatang'] as $thongTinSanPham) {
                            $dataChiTietPhieuXuat = [
                                NULL, //machitietphieuxuat  tu dong
                                $thongTinPhieuXuat->maphieuxuat,
                                $thongTinSanPham->masanpham,
                                $thongTinSanPham->baohanh,
                                $ctgh['soluongmua'], //so luong qua tang theo so luong mua cua san pham
                                0 //don gia qua tang la 0 dong
                            ];
                            $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
                        }
                    }
                    $dataChiTietPhieuXuat = [
                        NULL, //machitietphieuxuat  tu dong
                        $thongTinPhieuXuat->maphieuxuat,
                        $ctgh['masanpham'],
                        $ctgh['baohanh'],
                        $ctgh['soluongmua'],
                        $donGia
                    ];
                    $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
                }
                session()->forget('gioHang');
                return redirect()->route('/')->with('thongbao', '?????t h??ng th??nh c??ng, s??? c?? nh??n vi??n giao h??ng cho b???n trong 24h t???i!');
            }
            return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
        }
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.thanhtoan', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function xulythanhtoan(Request $request)
    {
        if (empty(session('gioHang'))) return redirect()->route('giohang');
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "?????t h??ng") { // *******************************************************************************************dat hang // thanh toan
            $rules = [
                'hoTen' => 'required|string|max:50|min:3',
                'soDienThoai' => 'required|numeric|digits:10',
                'diaChi' => 'required|string|max:255|min:3',
                'tongTien' => 'required|numeric',
                'hinhThucThanhToan' => 'required|integer|between:0,2',
                'ghiChu' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'integer' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???'
            ];
            $attributes = [
                'hoTen' => 'H??? t??n',
                'soDienThoai' => 'S??? ??i???n tho???i',
                'diaChi' => '?????a ch???',
                'tongTien' => 'T???ng ti???n',
                'hinhThucThanhToan' => 'H??nh th???c thanh to??n',
                'ghiChu' => 'Ghi ch??'
            ];
            $request->validate($rules, $messages, $attributes);
            if (isset($request->taoTaiKhoan)) {
                if ($request->taoTaiKhoan == "on") {
                    $rules = [
                        'email' => 'required|email|max:150|min:5|unique:nguoidung,email',
                        'matKhau' => 'required|string|max:32|min:8',
                        'nhapLaiMatKhau' => 'required|string|max:32|min:8|same:matKhau'
                    ];
                    $messages = [
                        'required' => ':attribute b???t bu???c nh???p',
                        'unique' => ':attribute ???? t???n t???i',
                        'string' => ':attribute ???? nh???p sai',
                        'email' => ':attribute kh??ng ????ng ?????nh d???ng email',
                        'min' => ':attribute t???i thi???u :min k?? t???',
                        'max' => ':attribute t???i ??a :max k?? t???',
                        'same' => ':attribute kh??ng kh???p v???i m???t kh???u'
                    ];
                    $attributes = [
                        'email' => 'Email',
                        'matKhau' => 'M???t kh???u',
                        'nhapLaiMatKhau' => 'Nh???p l???i m???t kh???u',
                    ];
                    $request->validate($rules, $messages, $attributes);
                } else {
                    return back()->with('thongbao', '?????t h??ng th???t b???i!');
                }
            }
            if (isset($request->thongTinNguoiNhanKhac)) {
                if ($request->thongTinNguoiNhanKhac == "on") {
                    $rules = [
                        'hoTen' => 'required|string|max:50|min:3',
                        'soDienThoai' => 'required|numeric|digits:10',
                        'diaChi' => 'required|string|max:255|min:3',
                    ];
                    $messages = [
                        'required' => ':attribute b???t bu???c nh???p',
                        'string' => ':attribute ???? nh???p sai',
                        'numeric' => ':attribute ???? nh???p sai',
                        'min' => ':attribute t???i thi???u :min k?? t???',
                        'max' => ':attribute t???i ??a :max k?? t???',
                        'digits' => ':attribute kh??ng ????ng :digits k?? t???'
                    ];
                    $attributes = [
                        'hoTenNguoiNhan' => 'required|string|max:50|min:3',
                        'soDienThoaiNguoiNhan' => 'required|numeric|digits:10',
                        'diaChiNguoiNhan' => 'required|string|max:255|min:3',
                    ];
                    $request->validate($rules, $messages, $attributes);
                } else {
                    return back()->with('thongbao', '?????t h??ng th???t b???i!');
                }
            }
            $ngayTao = date("Y-m-d H:i:s");
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoSoDienThoai($request->soDienThoai); //tim nguoi dung da ton tai hay chua
            if (!empty($thongTinNguoiDung)) { //neu tim thay
                if ($thongTinNguoiDung->trangthai == 0) { //neu nguoi dung dang bi khoa
                    return back()->with('thongbao', 'Th??ng tin ng?????i ?????t hi???n ??ang b??? t???m kh??a do h???y qu?? nhi???u ????n!');
                }
                if (isset($request->taoTaiKhoan)) {
                    if ($request->taoTaiKhoan == "on") {
                        if (!empty($thongTinNguoiDung->email)) { //da co tai khoan nen khong the tao tai khoan moi
                            return back()->with('thongbao', 'Th??ng tin ng?????i ?????t ???? c?? t??i kho???n n??n kh??ng th??? t???o t??i kho???n!');
                        } else { //chua co tai khoan thi tao tai khoan
                            $dataNguoiDung = [
                                $request->email,
                                bcrypt($request->matKhau)
                            ];
                            $this->nguoiDung->taoTaiKhoanNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung); //tao tai khoan cho nguoi dung
                            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($thongTinNguoiDung->manguoidung); // cap nhat lai thong tin nguoi dung
                        }
                    }
                }
                $dataNguoiDung = [
                    $request->hoTen,
                    $thongTinNguoiDung->sodienthoai,
                    $request->diaChi,
                    $thongTinNguoiDung->loainguoidung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    $thongTinNguoiDung->email,
                    $thongTinNguoiDung->password
                ];
                $this->nguoiDung->suaNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung); //sua lai thong tin nguoi dung
                $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($thongTinNguoiDung->manguoidung); // cap nhat lai thong tin nguoi dung
            } else {
                $dataNguoiDung = [
                    NULL, //manguoidung tu tang
                    $request->hoTen,
                    $request->soDienThoai,
                    $request->diaChi,
                    1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                    0, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    NULL, //email
                    NULL, //matkhau
                    $ngayTao
                ];
                if (isset($request->taoTaiKhoan)) {
                    if ($request->taoTaiKhoan == "on") {
                        $dataNguoiDung = [
                            NULL, //manguoidung tu tang
                            $request->hoTen,
                            $request->soDienThoai,
                            $request->diaChi,
                            1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                            0, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                            $request->email,
                            bcrypt($request->matKhau),
                            $ngayTao
                        ];
                    }
                }
                $this->nguoiDung->themNguoiDung($dataNguoiDung); //them nguoi dung vao database
                $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoNgayTao($ngayTao); //tim nguoi dung vua them
            }
            $congNo = -$request->tongTien;
            $maGiamGiaDuocApDung = NULL;
            if (!empty(session('maGiamGia'))) {
                $thongTinMaGiamGia = $this->maGiamGia->timMaGiamGiaTheoMa(session('maGiamGia')->magiamgia); //tim ma giam gia
                if (!empty($thongTinMaGiamGia)) {
                    if (strtotime($thongTinMaGiamGia->ngayketthuc) - strtotime(date('Y-m-d')) >= 0) { //neu con han su dung
                        $maGiamGiaDuocApDung = $thongTinMaGiamGia->magiamgia;
                        $congNo += $thongTinMaGiamGia->sotiengiam;
                        if ($congNo > 0) $congNo = 0;
                        session()->forget('maGiamGia');
                    } else {
                        return back()->with('thongbao', 'M?? gi???m gi?? ???? h???t h???n s??? d???ng!');
                    }
                } else {
                    return back()->with('thongbao', 'M?? gi???m gi?? kh??ng t???n t???i!');
                }
            }
            $dataPhieuXuat = [
                NULL, //maphieuxuat tu dong
                $thongTinNguoiDung->hoten,    // hotennguoinhan,
                $thongTinNguoiDung->sodienthoai,    // sodienthoainguoinhan,
                $thongTinNguoiDung->diachi,    // diachinguoinhan,
                $thongTinNguoiDung->manguoidung,
                $maGiamGiaDuocApDung,    // magiamgia,
                $request->ghiChu,
                $request->tongTien,
                1,    // tinhtranggiaohang,  	0 l?? ???? h???y, 1 l?? ch??? x??c nh???n, 2 l?? ??ang chu???n b??? h??ng, 3 l?? ??ang giao, 4 l?? ???? giao th??nh c??ng
                $request->hinhThucThanhToan,    // hinhthucthanhtoan,   0 l?? ti???n m???t, 1 l?? chuy???n kho???n, 2 l?? atm qua vpn
                $congNo,    // congno, 0 l?? ???? thanh to??n, !=0 l?? c??ng n???
                $ngayTao    // ngaytao
            ];
            if (isset($request->thongTinNguoiNhanKhac)) {
                if ($request->thongTinNguoiNhanKhac == "on") {
                    $dataPhieuXuat = [
                        NULL, //maphieuxuat tu dong
                        $request->hoTenNguoiNhan,    // hotennguoinhan,
                        $request->soDienThoaiNguoiNhan,    // sodienthoainguoinhan,
                        $request->diaChiNguoiNhan,    // diachinguoinhan,
                        $thongTinNguoiDung->manguoidung,
                        $maGiamGiaDuocApDung,    // magiamgia,
                        $request->ghiChu,
                        $request->tongTien,
                        1,    // tinhtranggiaohang,  	0 l?? ???? h???y, 1 l?? ch??? x??c nh???n, 2 l?? ??ang chu???n b??? h??ng, 3 l?? ??ang giao, 4 l?? ???? giao th??nh c??ng
                        $request->hinhThucThanhToan,    // hinhthucthanhtoan,   0 l?? ti???n m???t, 1 l?? chuy???n kho???n, 2 l?? atm qua vpn
                        $congNo,    // congno, 0 l?? ???? thanh to??n, !=0 l?? c??ng n???
                        $ngayTao    // ngaytao
                    ];
                }
            }
            if ($request->hinhThucThanhToan == 2) {
                $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
                $vnp_Returnurl = url('thanhtoan') . "";
                $vnp_TmnCode = "HHZDYEDW"; //M?? website t???i VNPAY
                $vnp_HashSecret = "XJICDMDJSFFIPHQFLAUQTGXVNBXJQATE"; //Chu???i b?? m???t
                $vnp_TxnRef = time() . ""; //M?? ????n h??ng. Trong th???c t??? Merchant c???n insert ????n h??ng v??o DB v?? g???i m?? n??y sang VNPAY
                $vnp_OrderInfo = json_encode($dataPhieuXuat) . "";
                $vnp_OrderType = 'billpayment';
                $vnp_Amount = (-$congNo) * 100;
                $vnp_Locale = 'vn';
                $vnp_BankCode = 'NCB';
                $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
                $inputData = array(
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => $vnp_TmnCode,
                    "vnp_Amount" => $vnp_Amount,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $vnp_IpAddr,
                    "vnp_Locale" => $vnp_Locale,
                    "vnp_OrderInfo" => $vnp_OrderInfo,
                    "vnp_OrderType" => $vnp_OrderType,
                    "vnp_ReturnUrl" => $vnp_Returnurl,
                    "vnp_TxnRef" => $vnp_TxnRef
                );
                if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                    $inputData['vnp_BankCode'] = $vnp_BankCode;
                }
                if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
                    $inputData['vnp_Bill_State'] = $vnp_Bill_State;
                }
                ksort($inputData);
                $query = "";
                $i = 0;
                $hashdata = "";
                foreach ($inputData as $key => $value) {
                    if ($i == 1) {
                        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                    } else {
                        $hashdata .= urlencode($key) . "=" . urlencode($value);
                        $i = 1;
                    }
                    $query .= urlencode($key) . "=" . urlencode($value) . '&';
                }
                $vnp_Url = $vnp_Url . "?" . $query;
                if (isset($vnp_HashSecret)) {
                    $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //
                    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
                }
                $returnData = array(
                    'code' => '00', 'message' => 'success', 'data' => $vnp_Url
                );
                if ($request->thaoTac == "?????t h??ng") {
                    return redirect()->to($vnp_Url);
                } else {
                    echo json_encode($returnData);
                }
            }
            $this->phieuXuat->themPhieuXuat($dataPhieuXuat); //them phieu xuat vao database
            $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoNgayTao($ngayTao); //tim phieu xuat vua them
            foreach (session('gioHang') as $ctgh) {
                $donGia = $ctgh['giaban'];
                if (!empty($ctgh['giakhuyenmai'])) {
                    $donGia = $ctgh['giakhuyenmai'];
                }
                if (!empty($ctgh['quatang'])) { // xem chi tiet gio hang san pham do co qua tang khong neu co qua tang xuat them chi tiet phieu xuat 0 dong
                    foreach ($ctgh['quatang'] as $thongTinSanPham) {
                        $dataChiTietPhieuXuat = [
                            NULL, //machitietphieuxuat  tu dong
                            $thongTinPhieuXuat->maphieuxuat,
                            $thongTinSanPham->masanpham,
                            $thongTinSanPham->baohanh,
                            $ctgh['soluongmua'], //so luong qua tang theo so luong mua cua san pham
                            0 //don gia qua tang la 0 dong
                        ];
                        $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
                    }
                }
                $dataChiTietPhieuXuat = [
                    NULL, //machitietphieuxuat  tu dong
                    $thongTinPhieuXuat->maphieuxuat,
                    $ctgh['masanpham'],
                    $ctgh['baohanh'],
                    $ctgh['soluongmua'],
                    $donGia
                ];
                $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
            }
            session()->forget('gioHang');
            return redirect()->route('/')->with('thongbao', '?????t h??ng th??nh c??ng, s??? c?? nh??n vi??n li??n h??? b???n ????? x??c nh???n trong 24h t???i!');
        }
        return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
    }
    public function yeuthich()
    {
        if (empty(session('yeuThich'))) return redirect()->route('/')->with('thongbao', 'Danh s??ch y??u th??ch ch??a c?? s???n ph???m!');
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.yeuthich', compact(
            'danhSachHangSanXuat'
        ));
    }
    public function xulyyeuthich(Request $request)
    {
        $request->validate(['thaotac' => 'required|string']);
        if ($request->thaotac == "boyeuthich") { // *******************************************************************************************bo yeu thich
            $rules = [
                'masp' => 'required|integer|exists:sanpham,masanpham'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'masp' => 'M?? s???n ph???m'
            ];
            $request->validate($rules, $messages, $attributes);
            $yeuThich = [];
            $flag = false;
            if (!empty(session('yeuThich'))) {
                foreach (session('yeuThich') as $ctyt) { // duyet qua gio hang cu
                    if ($request->masp == $ctyt['masanpham']) { //neu chi tiet yeu thich co ma san pham trung voi san pham can xoa trong yeu thich thi khong dc them vao yeu thich moi
                        $flag = true;
                    } else {  //con neu chi tiet gio hang khac voi san pham can xoa trong gio hang
                        $yeuThich = array_merge($yeuThich, [$ctyt]); // thi them chi tiet gio hang do vao gio
                    }
                }
                if ($flag) {
                    session(['yeuThich' => $yeuThich]); //thay gio hang cu bang gio hang moi
                    return back()->with('thongbao', 'B??? y??u th??ch SP' . $request->masp . ' th??nh c??ng!');
                }
            }
            if (empty(session('yeuThich'))) {
                session()->forget('yeuThich');
            }
            return back()->with('thongbao', 'B??? y??u th??ch SP' . $request->masp . ' th???t b???i!');
        }
        if ($request->thaotac == "yeuthich") { // *******************************************************************************************yeu thich
            $rules = [
                'masp' => 'required|integer|exists:sanpham,masanpham'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'masp' => 'M?? s???n ph???m'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->masp); //tim san pham da them vao yeu thich
            $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPham->mathuvienhinh); //tim hinh san pham da them vao yeu thich
            if (!empty($thongTinSanPham) && !empty($thongTinHinh)) {
                $chiTietYeuThich = [
                    'masanpham' => $thongTinSanPham->masanpham,
                    'tensanpham' => $thongTinSanPham->tensanpham,
                    'giaban' => $thongTinSanPham->giaban,
                    'giakhuyenmai' => $thongTinSanPham->giakhuyenmai,
                    'soluongtonkho' => $thongTinSanPham->soluong,
                    'hinh' => $thongTinHinh->hinh1
                ];
                $yeuThich = [];
                if (!empty(session('yeuThich'))) {
                    foreach (session('yeuThich') as $ctyt) {
                        if ($ctyt['masanpham'] == $chiTietYeuThich['masanpham']) { // tim xem chi tiet gio hang vua them co san trong gio hang chua
                            return back()->with('thongbao', 'SP' . $request->masp . ' ???? c?? trong danh s??ch y??u th??ch!');
                        }
                        $yeuThich = array_merge($yeuThich, [$ctyt]);
                    }
                }
                $yeuThich = array_merge($yeuThich, [$chiTietYeuThich]);
                session(['yeuThich' => $yeuThich]);
                return back()->with('thongbao', 'Y??u th??ch SP' . $request->masp . ' th??nh c??ng!');
            }
        }
        return redirect()->route('/')->with('thongbao', 'Thao t??c th???t b???i vui l??ng th??? l???i!');
    }
    public function timkiem(Request $request)
    {
        $boLoc = [];
        $tuKhoa = NULL;
        $sapXep = NULL;
        if (!empty($request->boloc)) {
            if ($request->boloc == -1) { //laptop
                $boLoc[] = ['sanpham.loaisanpham', '=', 0];
            } else if ($request->boloc == -2) { //phukien
                $boLoc[] = ['sanpham.loaisanpham', '=', 1];
            } else if ($request->boloc != 0) { //mahang
                $boLoc[] = ['sanpham.mahang', '=', $request->boloc];
            }
        }
        if (!empty($request->tukhoa)) {
            $tuKhoa = $request->tukhoa;
        }
        if (!empty($request->sapxep)) {
            $sapXep = $request->sapxep;
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPhamTheoBoLoc($boLoc, $tuKhoa, $sapXep);
        $danhSachLaptop = $this->laptop->layDanhSachLaptop();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        return view('user.timkiem', compact(
            'danhSachSanPham',
            'danhSachLaptop',
            'danhSachThuVienHinh',
            'danhSachHangSanXuat'
        ));
    }
}