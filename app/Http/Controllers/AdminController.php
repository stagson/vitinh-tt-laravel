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
use App\Models\PhieuNhap;
use App\Models\ChiTietPhieuNhap;
use App\Models\PhieuXuat;
use App\Models\ChiTietPhieuXuat;
use App\Models\MaGiamGia;
use App\Models\LoiPhanHoi;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use PDF;
class AdminController extends Controller
{
    //
    private $sanPham;
    private $laptop;
    private $phuKien;
    private $thuVienHinh;
    private $hangSanXuat;
    private $quaTang;
    private $nguoiDung;
    private $phieuNhap;
    private $chiTietPhieuNhap;
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
        $this->phieuNhap = new PhieuNhap();
        $this->chiTietPhieuNhap = new ChiTietPhieuNhap();
        $this->phieuXuat = new PhieuXuat();
        $this->chiTietPhieuXuat = new ChiTietPhieuXuat();
        $this->maGiamGia = new MaGiamGia();
        $this->loiPhanHoi = new LoiPhanHoi();
    }
    public function tongquan(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $soLuongLaptop = 0;
        $soLuongPhuKien = 0;
        foreach ($danhSachSanPham as $sanpham) {
            if ($sanpham->loaisanpham == 0) { // la laptop
                $soLuongLaptop += $sanpham->soluong;
            }
            if ($sanpham->loaisanpham == 1) { // la phu kien
                $soLuongPhuKien += $sanpham->soluong;
            }
        }
        $soLuongDonHang = count($this->phieuXuat->layDanhSachPhieuXuat());
        $soLuongNguoiDung = count($this->nguoiDung->layDanhSachNguoiDung());
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        $danhSachLoiPhanHoi = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc(NULL);
        $doanhThuTuanNay = $this->phieuXuat->doanhThuTuanNay();
        if (isset($request->thaotac)) {
            if ($request->thaotac == "doitrangthai") { // *******************************************************************************************doi trang thai loi phan hoi// loi nhan lien he
                $rules = [
                    'maloiphanhoi' => 'required|integer|exists:loiphanhoi,maloiphanhoi'
                ];
                $messages = [
                    'required' => ':attribute b???t bu???c nh???p',
                    'exists' => ':attribute kh??ng t???n t???i',
                    'integer' => ':attribute nh???p sai'
                ];
                $attributes = [
                    'maloiphanhoi' => 'M?? l???i ph???n h???i'
                ];
                $request->validate($rules, $messages, $attributes);
                $thongTinLoiPhanHoi = $this->loiPhanHoi->timLoiPhanHoiTheoMa($request->maloiphanhoi);
                if ($thongTinLoiPhanHoi->trangthai == 0) {
                    $thongTinLoiPhanHoi->trangthai = 1;
                } elseif ($thongTinLoiPhanHoi->trangthai == 1) {
                    $thongTinLoiPhanHoi->trangthai = 0;
                }
                $dataLoiPhanHoi = [
                    $thongTinLoiPhanHoi->trangthai
                ];
                $this->loiPhanHoi->doiTrangThaiLoiPhanHoi($dataLoiPhanHoi, $thongTinLoiPhanHoi->maloiphanhoi);
                return redirect('tongquan#loiphanhoi');
            } elseif ($request->thaotac == "doitrangthaitatca" && !empty($danhSachLoiPhanHoi)) {
                $this->loiPhanHoi->doiTrangThaiLoiPhanHoiTatCa();
                return redirect('tongquan#loiphanhoi');
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                'Vui l??ng th??? l???i!'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        return view('admin.tongquan', compact(
            'soLuongLaptop',
            'soLuongPhuKien',
            'soLuongDonHang',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachLoiPhanHoi',
            'doanhThuTuanNay',
            'soLuongNguoiDung'
        ));
    }
    public function xulysanpham(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "c???p nh???t gi??") { // *******************************************************************************************cap nhat gia san pham
            $rules = [
                'maSanPhamSuaGia' => 'required|integer|exists:sanpham,masanpham',
                'giaBan' => 'required|string|max:255|min:1'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???'
            ];
            $attributes = [
                'maSanPhamSuaGia' => 'M?? s???n ph???m',
                'giaBan' => 'Gi?? b??n'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->maSanPhamSuaGia); //tim san pham
            $giaBan = explode(',', $request->giaBan);
            $temp = "";
            foreach ($giaBan as $gb) {
                $temp = $temp . $gb;
            }
            $giaBan = $temp;
            if (!is_numeric($giaBan) || $giaBan <= $thongTinSanPham->gianhap || $giaBan <= 0) { // gia ban nhap vao khong phai ky tu so hoac thap hon gia nhap, quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Gi?? b??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $dataSanPham = [
                $giaBan,
                NULL //giakhuyenmai
            ];
            if (isset($request->giaKhuyenMaiCheck)) {
                if ($request->giaKhuyenMaiCheck == "on") {
                    $rules = [
                        'giaKhuyenMai' => 'required|string|max:255|min:1'
                    ];
                    $messages = [
                        'required' => ':attribute b???t bu???c nh???p',
                        'string' => ':attribute nh???p sai',
                        'min' => ':attribute t???i thi???u :min k?? t???',
                        'max' => ':attribute t???i ??a :max k?? t???'
                    ];
                    $attributes = [
                        'giaKhuyenMai' => 'Gi?? khuy???n m??i'
                    ];
                    $request->validate($rules, $messages, $attributes);
                    $giaKhuyenMai = explode(',', $request->giaKhuyenMai);
                    $temp = "";
                    foreach ($giaKhuyenMai as $gkm) {
                        $temp = $temp . $gkm;
                    }
                    $giaKhuyenMai = $temp;
                    if (!is_numeric($giaKhuyenMai) || $giaKhuyenMai >= $giaBan || $giaKhuyenMai <= 0) { // gia khuyen mai nhap vao khong phai ky tu so hoac thap hon gia nhap hoac lon hon gia ban, quay lai trang truoc va bao loi
                        return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Gi?? khuy???n m??i nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                    }
                    $dataSanPham = [
                        $giaBan,
                        $giaKhuyenMai //giakhuyenmai
                    ];
                } else {
                    return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Gi?? khuy???n m??i nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                }
            }
            $this->sanPham->capNhatGia($dataSanPham, $thongTinSanPham->masanpham); //cap nhat gia san pham tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'C???p nh???t gi?? s???n ph???m th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function laptop()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachLaptop = $this->laptop->layDanhSachLaptop();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachQuaTang = $this->quaTang->layDanhSachQuaTang();
        $danhSachHangSanXuatLaptop = []; // loc lai danh sach theo loai hang san xuat laptop can xem
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 0) {
                $danhSachHangSanXuatLaptop = array_merge($danhSachHangSanXuatLaptop, [$hangSanXuat]);
            }
        }
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.laptop', compact(
            'danhSachSanPham',
            'danhSachLaptop',
            'danhSachThuVienHinh',
            'danhSachHangSanXuatLaptop',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachQuaTang'
        ));
    }
    public function xulylaptop(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "x??a laptop") { // *******************************************************************************************xoa laptop
            $rules = [
                'maSanPhamXoa' => 'required|integer|exists:sanpham,masanpham'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai'
            ];
            $attributes = [
                'maSanPhamXoa' => 'M?? s???n ph???m'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->maSanPhamXoa); //tim san pham
            $thongTinChiTietPhieuNhap = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaSanPham($thongTinSanPham->masanpham);
            if (!empty($thongTinChiTietPhieuNhap)) {
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th???t b???i'
                )->with(
                    'thongbao',
                    'Laptop ???? t???n t???i trong phi???u nh???p [PN' . $thongTinChiTietPhieuNhap[0]->maphieunhap . '] n??n kh??ng th??? x??a'
                )->with(
                    'loaithongbao',
                    'danger'
                );
            }
            $thongTinChiTietPhieuXuat = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaSanPham($thongTinSanPham->masanpham);
            if (!empty($thongTinChiTietPhieuXuat)) {
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th???t b???i'
                )->with(
                    'thongbao',
                    'Laptop ???? t???n t???i trong phi???u xu???t [PX' . $thongTinChiTietPhieuXuat[0]->maphieuxuat . '] n??n kh??ng th??? x??a'
                )->with(
                    'loaithongbao',
                    'danger'
                );
            }
            if (!empty($thongTinSanPham)) {
                $this->sanPham->xoaSanPham($thongTinSanPham->masanpham); //xoa san pham tren database
                if ($thongTinSanPham->loaisanpham == 0 && !empty($thongTinSanPham->malaptop)) {
                    $thongTinLaptop = $this->laptop->timLaptopTheoMa($thongTinSanPham->malaptop); //tim laptop
                    if (!empty($thongTinLaptop)) {
                        $this->laptop->xoaLaptop($thongTinLaptop->malaptop); //xoa laptop tren database
                    }
                }
                $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPham->mathuvienhinh); //tim thu vien hinh
                if (!empty($thongTinHinh)) {
                    $this->thuVienHinh->xoaThuVienHinh($thongTinHinh->mathuvienhinh); //xoa thu vien hinh tren database
                    foreach ($thongTinHinh as $giaTri) {
                        if (!empty($giaTri)) {
                            $duongDanHinhCanXoa = 'img/sanpham/' . $giaTri;
                            if (File::exists($duongDanHinhCanXoa)) {
                                File::delete($duongDanHinhCanXoa); //xoa thu vien hinh tren host sever
                            }
                        }
                    }
                }
                $thongTinQuaTang = $this->quaTang->timQuaTangTheoMa($thongTinSanPham->maquatang); //tim qua tang
                if (!empty($thongTinQuaTang)) {
                    $this->quaTang->xoaQuaTang($thongTinQuaTang->maquatang); //xoa qua tang tren database
                }
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th??nh c??ng'
                )->with(
                    'thongbao',
                    'X??a laptop th??nh c??ng'
                )->with(
                    'loaithongbao',
                    'success'
                );
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                'X??a laptop th???t b???i'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        if ($request->thaoTac == "s???a laptop") { // *******************************************************************************************sua laptop
            $rules = [
                'maSanPhamSua' => 'required|integer|exists:sanpham,masanpham',
                'tenSanPhamSua' => 'required|string|max:150|min:3',
                'baoHanhSua' => 'required|integer|between:1,48',
                'cpuSua' => 'required|string|max:50|min:3',
                'hangSanXuatSua' => 'required|integer|exists:hangsanxuat,mahang',
                'ramSua' => 'required|integer|between:4,32',
                'cardDoHoaSua' => 'required|integer|between:0,2',
                'oCungSua' => 'required|integer|between:128,512',
                'manHinhSua' => 'required|numeric|between:10,30',
                'nhuCauSua' => 'required|string|max:50|min:3',
                'tinhTrangSua' => 'required|boolean',
                'quaTangSua' => 'required|array|size:5',
                'hinhSanPhamSua' => 'array|between:1,5',
                'hinhSanPhamSua.*' => 'image|dimensions:min_width=500,min_height=450,max_width=500,max_height=450',
                'moTaSua' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i thi???u :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'size' => ':attribute kh??ng ????ng s??? l?????ng (:size)',
                'exists' => ':attribute kh??ng t???n t???i',
                'numeric' => ':attribute ph???i l?? k?? t??? s???',
                'integer' => ':attribute nh???p sai',
                'boolean' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai',
                'hinhSanPhamSua.array' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPhamSua.between' => 'H??nh s???n ph???m v?????t qu?? s??? l?????ng cho ph??p',
                'hinhSanPhamSua.*.image' => 'H??nh s???n ph???m kh??ng ????ng ?????nh d???ng',
                'hinhSanPhamSua.*.dimensions' => 'H??nh s???n ph???m kh??ng ????ng k??ch th?????c :min_width x :min_height'
            ];
            $attributes = [
                'tenSanPhamSua' => 'T??n s???n ph???m',
                'baoHanhSua' => 'B???o h??nh',
                'cpuSua' => 'Cpu',
                'hangSanXuatSua' => 'H??ng s???n xu???t',
                'ramSua' => 'Ram',
                'cardDoHoaSua' => 'Card ????? h???a',
                'oCungSua' => '??? c???ng',
                'manHinhSua' => 'M??n h??nh',
                'nhuCauSua' => 'Nhu c???u',
                'tinhTrangSua' => 'T??nh tr???ng',
                'quaTangSua' => 'Qua t???ng',
                'moTaSua' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->maSanPhamSua); //tim san pham

            // ***********Xu ly sua san pham
            if ($thongTinSanPham->tensanpham != $request->tenSanPhamSua) { //so sanh ten san pham
                $sanPhamTrungTenSapDoi = $this->sanPham->timSanPhamTheoTen($request->tenSanPhamSua);
                if (empty($sanPhamTrungTenSapDoi)) { //ten sap doi khong bi trung
                    $thongTinSanPham->tensanpham = $request->tenSanPhamSua;
                } else {
                    return back()->with(
                        'tieudethongbao',
                        'Thao t??c th???t b???i'
                    )->with(
                        'thongbao',
                        'S???a th??ng tin laptop th???t b???i, t??n s???n ph???m ???? t???n t???i'
                    )->with(
                        'loaithongbao',
                        'danger'
                    );
                }
            }
            if ($thongTinSanPham->baohanh != $request->baoHanhSua) { //so sanh bao hanh
                $thongTinSanPham->baohanh = $request->baoHanhSua;
            }
            if ($thongTinSanPham->mota != $request->moTaSua) { //so sanh mo ta
                $thongTinSanPham->mota = $request->moTaSua;
            }
            if ($thongTinSanPham->mahang != $request->hangSanXuatSua) { //so sanh hang san xuat
                $thongTinSanPham->mahang = $request->hangSanXuatSua;
            }
            $dataSanPham = [
                $thongTinSanPham->tensanpham,
                $thongTinSanPham->baohanh,
                $thongTinSanPham->mota,
                $thongTinSanPham->mahang
            ];
            $this->sanPham->suaSanPham($dataSanPham, $thongTinSanPham->masanpham); // sua thong tin san pham tren database
            // ***********Xu ly sua laptop
            if ($thongTinSanPham->loaisanpham == 0 && !empty($thongTinSanPham->malaptop)) { // la laptop
                $dataLaptop = [
                    $thongTinSanPham->tensanpham,
                    $request->cpuSua,
                    (int)$request->ramSua,
                    (int)$request->cardDoHoaSua,
                    (int)$request->oCungSua,
                    (float)$request->manHinhSua,
                    $request->nhuCauSua,
                    (int)$request->tinhTrangSua
                ];
                $this->laptop->suaLaptop($dataLaptop, $thongTinSanPham->malaptop); // sua thong tin laptop tren database
            }
            // ***********Xu ly them thu vien hinh (neu co)
            if (isset($request->hinhSanPhamSua)) {
                // ***********up hinh moi vao len host
                $tenHinh = [NULL, NULL, NULL, NULL, NULL];
                $dem = 0;
                if ($request->has('hinhSanPhamSua')) {
                    foreach ($request->hinhSanPhamSua as $hinh) {
                        $tenHinh[$dem] = $request->tenSanPhamSua . '-' . time() . '-' . $dem . '.' . $hinh->guessExtension();
                        $hinh->move(public_path('img/sanpham'), $tenHinh[$dem]);
                        $dem++;
                    }
                }
                // ***********xoa hinh cu tren host
                $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPham->mathuvienhinh); //tim thu vien hinh
                if (!empty($thongTinHinh)) {
                    foreach ($thongTinHinh as $giaTri) {
                        if (!empty($giaTri)) {
                            $duongDanHinhCanXoa = 'img/sanpham/' . $giaTri;
                            if (File::exists($duongDanHinhCanXoa)) {
                                File::delete($duongDanHinhCanXoa); //xoa thu vien hinh tren host sever
                            }
                        }
                    }
                }
                // ***********sua thong tin thu vien hinh tren database
                $dataHinh = [
                    $thongTinSanPham->tensanpham,
                    $tenHinh[0], //hinh 1
                    $tenHinh[1], //hinh 2
                    $tenHinh[2], //hinh 3
                    $tenHinh[3], //hinh 4
                    $tenHinh[4], //hinh 5
                ];
                $this->thuVienHinh->suaThuVienHinh($dataHinh, $thongTinSanPham->mathuvienhinh); //sua thong tin thu vien hinh tren database
            }
            // ***********Xu ly sua qua tang
            $dataQuaTang = [
                $thongTinSanPham->tensanpham, //ten san pham [0]
                NULL, //ma san pham 1 [1]
                NULL, //ma san pham 2 [2]
                NULL, //ma san pham 3 [3]
                NULL, //ma san pham 4 [4]
                NULL, //ma san pham 5 [5]
            ];
            $dem = 1;
            $quaTangSua = $request->quaTangSua;
            for ($i = 0; $i < count($quaTangSua); $i++) {
                if ($quaTangSua[$i] != NULL) {
                    for ($j = $i + 1; $j < count($quaTangSua); $j++) {
                        if ($quaTangSua[$i] == $quaTangSua[$j]) {
                            $quaTangSua[$j] = NULL;
                        }
                    }
                }
            }
            foreach ($quaTangSua as $maSanPhamQuaTang) {
                if (!empty($maSanPhamQuaTang)) {
                    $thongTinSanPhamTang = $this->sanPham->timSanPhamTheoMa($maSanPhamQuaTang);
                    if (!empty($thongTinSanPhamTang)) {
                        $dataQuaTang[$dem] = $thongTinSanPhamTang->masanpham;
                        $dem++;
                    }
                }
            }
            $this->quaTang->suaQuaTang($dataQuaTang, $thongTinSanPham->maquatang); // sua thong tin qua tang tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'S???a th??ng tin laptop th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "th??m laptop") { // *******************************************************************************************them laptop
            $rules = [
                'tenSanPham' => 'required|string|max:150|min:3|unique:sanpham',
                'baoHanh' => 'required|integer|between:1,48',
                'cpu' => 'required|string|max:50|min:3',
                'hangSanXuat' => 'required|integer|exists:hangsanxuat,mahang',
                'ram' => 'required|integer|between:4,32',
                'cardDoHoa' => 'required|integer|between:0,2',
                'oCung' => 'required|integer|between:128,512',
                'manHinh' => 'required|numeric|between:10,30',
                'nhuCau' => 'required|string|max:50|min:3',
                'tinhTrang' => 'required|boolean',
                'quaTang' => 'required|array|size:5',
                'hinhSanPham' => 'required|array|between:1,5',
                'hinhSanPham.*' => 'image|dimensions:min_width=500,min_height=450,max_width=500,max_height=450',
                'moTa' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'size' => ':attribute kh??ng ????ng s??? l?????ng (:size)',
                'unique' => ':attribute ???? t???n t???i',
                'exists' => ':attribute kh??ng t???n t???i',
                'numeric' => ':attribute ph???i l?? k?? t??? s???',
                'integer' => ':attribute nh???p sai',
                'boolean' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai',
                'hinhSanPham.required' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPham.array' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPham.between' => 'H??nh s???n ph???m v?????t qu?? s??? l?????ng cho ph??p',
                'hinhSanPham.*.image' => 'H??nh s???n ph???m kh??ng ????ng ?????nh d???ng',
                'hinhSanPham.*.dimensions' => 'H??nh s???n ph???m kh??ng ????ng k??ch th?????c :min_width x :min_height'
            ];
            $attributes = [
                'tenSanPham' => 'T??n s???n ph???m',
                'baoHanh' => 'B???o h??nh',
                'cpu' => 'Cpu',
                'hangSanXuat' => 'H??ng s???n xu???t',
                'ram' => 'Ram',
                'cardDoHoa' => 'Card ????? h???a',
                'oCung' => '??? c???ng',
                'manHinh' => 'M??n h??nh',
                'nhuCau' => 'Nhu c???u',
                'tinhTrang' => 'T??nh tr???ng',
                'quaTang' => 'Q??a t???ng',
                'moTa' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            // ***********Xu ly them qua tang
            $dataQuaTang = [
                NULL, //ma qua tang [0]
                $request->tenSanPham, //ten san pham [1]
                NULL, //ma san pham 1 [2]
                NULL, //ma san pham 2 [4]
                NULL, //ma san pham 3 [5]
                NULL, //ma san pham 4 [6]
                NULL, //ma san pham 5 [7]
            ];
            $dem = 2;
            $quaTang = $request->quaTang;
            for ($i = 0; $i < count($quaTang); $i++) { // loc ma san pham tang bi trung
                if ($quaTang[$i] != NULL) {
                    for ($j = $i + 1; $j < count($quaTang); $j++) {
                        if ($quaTang[$i] == $quaTang[$j]) {
                            $quaTang[$j] = NULL;
                        }
                    }
                }
            }
            foreach ($quaTang as $maSanPhamQuaTang) {
                if (!empty($maSanPhamQuaTang)) {
                    $thongTinSanPhamTang = $this->sanPham->timSanPhamTheoMa($maSanPhamQuaTang);
                    if (!empty($thongTinSanPhamTang)) {
                        $dataQuaTang[$dem] = $thongTinSanPhamTang->masanpham;
                        $dem++;
                    }
                }
            }

            // ***********Xu ly them thu vien hinh
            $tenHinh = [NULL, NULL, NULL, NULL, NULL];
            $dem = 0;
            if ($request->has('hinhSanPham')) {
                foreach ($request->hinhSanPham as $hinh) {
                    $tenHinh[$dem] = $request->tenSanPham . '-' . time() . '-' . $dem . '.' . $hinh->guessExtension();
                    $hinh->move(public_path('img/sanpham'), $tenHinh[$dem]);
                    $dem++;
                }
            }
            $dataHinh = [
                NULL, //ma hinh
                $request->tenSanPham,
                $tenHinh[0], //hinh 1
                $tenHinh[1], //hinh 2
                $tenHinh[2], //hinh 3
                $tenHinh[3], //hinh 4
                $tenHinh[4], //hinh 5
            ];
            // ***********Xu ly them laptop
            $dataLaptop = [
                NULL, //ma laptop
                $request->tenSanPham,
                $request->cpu,
                $request->ram,
                $request->cardDoHoa,
                $request->oCung,
                $request->manHinh,
                $request->nhuCau,
                $request->tinhTrang
            ];

            $this->quaTang->themQuaTang($dataQuaTang); //them vao database
            $thongTinQuaTang = $this->quaTang->timQuaTangTheoTenSanPham($request->tenSanPham); //tim qua tang vua them

            $this->thuVienHinh->themThuVienHinh($dataHinh); //them vao database
            $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoTenSanPham($request->tenSanPham); //tim thu vien hinh vua them

            $this->laptop->themLaptop($dataLaptop); //them vao database
            $thongTinLaptop = $this->laptop->timLaptopTheoTenSanPham($request->tenSanPham); //tim laptop vua them

            // ***********Xu ly them sanpham
            $dataSanPham = [
                NULL, //ma san pham
                $request->tenSanPham,
                $request->baoHanh,
                $request->moTa,
                0, //so luong
                0, //gia nhap
                0, //gia ban
                NULL, //gia khuyen mai
                $thongTinHinh->mathuvienhinh, //ma thu vien hinh
                $request->hangSanXuat, //ma hang
                $thongTinQuaTang->maquatang, //ma qua tang
                $thongTinLaptop->malaptop, //ma lap top
                NULL, //ma phu kien
                0 //loai san pham
                //ngaytao tu dong
            ];
            $this->sanPham->themSanPham($dataSanPham);
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m laptop m???i th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function phukien()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachPhuKien = $this->phuKien->layDanhSachPhuKien();
        $danhSachThuVienHinh = $this->thuVienHinh->layDanhSachThuVienHinh();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachQuaTang = $this->quaTang->layDanhSachQuaTang();
        $danhSachHangSanXuatPhuKien = []; // loc lai danh sach theo loai hang san xuat phu kien can xem
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 1) {
                $danhSachHangSanXuatPhuKien = array_merge($danhSachHangSanXuatPhuKien, [$hangSanXuat]);
            }
        }
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.phukien', compact(
            'danhSachSanPham',
            'danhSachPhuKien',
            'danhSachThuVienHinh',
            'danhSachHangSanXuatPhuKien',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachQuaTang'
        ));
    }
    public function xulyphukien(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "x??a ph??? ki???n") { // *******************************************************************************************xoa phu kien
            $rules = [
                'maSanPhamXoa' => 'required|integer|exists:sanpham,masanpham'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai'
            ];
            $attributes = [
                'maSanPhamXoa' => 'M?? s???n ph???m'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->maSanPhamXoa); //tim san pham
            $thongTinChiTietPhieuNhap = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaSanPham($thongTinSanPham->masanpham);
            if (!empty($thongTinChiTietPhieuNhap)) {
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th???t b???i'
                )->with(
                    'thongbao',
                    'Ph??? ki???n ???? t???n t???i trong phi???u nh???p [PN' . $thongTinChiTietPhieuNhap[0]->maphieunhap . '] n??n kh??ng th??? x??a'
                )->with(
                    'loaithongbao',
                    'danger'
                );
            }
            $thongTinChiTietPhieuXuat = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaSanPham($thongTinSanPham->masanpham);
            if (!empty($thongTinChiTietPhieuXuat)) {
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th???t b???i'
                )->with(
                    'thongbao',
                    'Ph??? ki???n ???? t???n t???i trong phi???u xu???t [PX' . $thongTinChiTietPhieuXuat[0]->maphieuxuat . '] n??n kh??ng th??? x??a'
                )->with(
                    'loaithongbao',
                    'danger'
                );
            }
            if (!empty($thongTinSanPham)) {
                $this->sanPham->xoaSanPham($thongTinSanPham->masanpham); //xoa san pham tren database
                if ($thongTinSanPham->loaisanpham == 1 && !empty($thongTinSanPham->maphukien)) {
                    $thongTinPhuKien = $this->phuKien->timPhuKienTheoMa($thongTinSanPham->maphukien); //tim phu kien
                    if (!empty($thongTinPhuKien)) {
                        $this->phuKien->xoaPhuKien($thongTinPhuKien->maphukien); //xoa phu kien tren database
                    }
                }
                $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPham->mathuvienhinh); //tim thu vien hinh
                if (!empty($thongTinHinh)) {
                    $this->thuVienHinh->xoaThuVienHinh($thongTinHinh->mathuvienhinh); //xoa thu vien hinh tren database
                    foreach ($thongTinHinh as $giaTri) {
                        if (!empty($giaTri)) {
                            $duongDanHinhCanXoa = 'img/sanpham/' . $giaTri;
                            if (File::exists($duongDanHinhCanXoa)) {
                                File::delete($duongDanHinhCanXoa); //xoa thu vien hinh tren host sever
                            }
                        }
                    }
                }
                $thongTinQuaTang = $this->quaTang->timQuaTangTheoMa($thongTinSanPham->maquatang); //tim qua tang
                if (!empty($thongTinQuaTang)) {
                    $this->quaTang->xoaQuaTang($thongTinQuaTang->maquatang); //xoa qua tang tren database
                }
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th??nh c??ng'
                )->with(
                    'thongbao',
                    'X??a ph??? ki???n th??nh c??ng'
                )->with(
                    'loaithongbao',
                    'success'
                );
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                'X??a ph??? ki???n th???t b???i'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        if ($request->thaoTac == "s???a ph??? ki???n") { // *******************************************************************************************sua phu kien
            $rules = [
                'maSanPhamSua' => 'required|integer|exists:sanpham,masanpham',
                'tenSanPhamSua' => 'required|string|max:150|min:3',
                'baoHanhSua' => 'required|integer|between:1,48',
                'hangSanXuatSua' => 'required|integer|exists:hangsanxuat,mahang',
                'tenLoaiPhuKienSua' => 'required|string|max:50|min:3',
                'quaTangSua' => 'required|array|size:5',
                'hinhSanPhamSua' => 'array|between:1,5',
                'hinhSanPhamSua.*' => 'image|dimensions:min_width=500,min_height=450,max_width=500,max_height=450',
                'moTaSua' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'size' => ':attribute kh??ng ????ng s??? l?????ng (:size)',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai',
                'boolean' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai',
                'hinhSanPhamSua.array' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPhamSua.between' => 'H??nh s???n ph???m v?????t qu?? s??? l?????ng cho ph??p',
                'hinhSanPhamSua.*.image' => 'H??nh s???n ph???m kh??ng ????ng ?????nh d???ng',
                'hinhSanPhamSua.*.dimensions' => 'H??nh s???n ph???m kh??ng ????ng k??ch th?????c :min_width x :min_height'
            ];
            $attributes = [
                'tenSanPhamSua' => 'T??n s???n ph???m',
                'baoHanhSua' => 'B???o h??nh',
                'hangSanXuatSua' => 'H??ng s???n xu???t',
                'tenLoaiPhuKienSua' => 'T??n lo???i ph??? ki???n',
                'quaTangSua' => 'Qua t???ng',
                'moTaSua' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($request->maSanPhamSua); //tim san pham
            // ***********Xu ly sua san pham
            if ($thongTinSanPham->tensanpham != $request->tenSanPhamSua) { //so sanh ten san pham
                $sanPhamTrungTenSapDoi = $this->sanPham->timSanPhamTheoTen($request->tenSanPhamSua);
                if (empty($sanPhamTrungTenSapDoi)) { //ten sap doi khong bi trung
                    $thongTinSanPham->tensanpham = $request->tenSanPhamSua;
                } else {
                    return back()->with(
                        'tieudethongbao',
                        'Thao t??c th???t b???i'
                    )->with(
                        'thongbao',
                        'S???a th??ng tin ph??? ki???n th???t b???i, t??n s???n ph???m ???? t???n t???i'
                    )->with(
                        'loaithongbao',
                        'danger'
                    );
                }
            }
            if ($thongTinSanPham->baohanh != $request->baoHanhSua) { //so sanh bao hanh
                $thongTinSanPham->baohanh = $request->baoHanhSua;
            }
            if ($thongTinSanPham->mota != $request->moTaSua) { //so sanh mo ta
                $thongTinSanPham->mota = $request->moTaSua;
            }
            if ($thongTinSanPham->mahang != $request->hangSanXuatSua) { //so sanh hang san xuat
                $thongTinSanPham->mahang = $request->hangSanXuatSua;
            }
            $dataSanPham = [
                $thongTinSanPham->tensanpham,
                $thongTinSanPham->baohanh,
                $thongTinSanPham->mota,
                $thongTinSanPham->mahang
            ];
            $this->sanPham->suaSanPham($dataSanPham, $thongTinSanPham->masanpham); // sua thong tin san pham tren database
            // ***********Xu ly sua phu kien
            if ($thongTinSanPham->loaisanpham == 1 && !empty($thongTinSanPham->maphukien)) { // la phu kien
                $dataPhuKien = [
                    $thongTinSanPham->tensanpham,
                    $request->tenLoaiPhuKienSua
                ];
                $this->phuKien->suaPhuKien($dataPhuKien, $thongTinSanPham->maphukien); // sua thong tin phu kien tren database
            }
            // ***********Xu ly them thu vien hinh (neu co)
            if (isset($request->hinhSanPhamSua)) {
                // ***********up hinh moi vao len host
                $tenHinh = [NULL, NULL, NULL, NULL, NULL];
                $dem = 0;
                if ($request->has('hinhSanPhamSua')) {
                    foreach ($request->hinhSanPhamSua as $hinh) {
                        $tenHinh[$dem] = $request->tenSanPhamSua . '-' . time() . '-' . $dem . '.' . $hinh->guessExtension();
                        $hinh->move(public_path('img/sanpham'), $tenHinh[$dem]);
                        $dem++;
                    }
                }
                // ***********xoa hinh cu tren host
                $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoMa($thongTinSanPham->mathuvienhinh); //tim thu vien hinh
                if (!empty($thongTinHinh)) {
                    foreach ($thongTinHinh as $giaTri) {
                        if (!empty($giaTri)) {
                            $duongDanHinhCanXoa = 'img/sanpham/' . $giaTri;
                            if (File::exists($duongDanHinhCanXoa)) {
                                File::delete($duongDanHinhCanXoa); //xoa thu vien hinh tren host sever
                            }
                        }
                    }
                }
                // ***********sua thong tin thu vien hinh tren database
                $dataHinh = [
                    $thongTinSanPham->tensanpham,
                    $tenHinh[0], //hinh 1
                    $tenHinh[1], //hinh 2
                    $tenHinh[2], //hinh 3
                    $tenHinh[3], //hinh 4
                    $tenHinh[4], //hinh 5
                ];
                $this->thuVienHinh->suaThuVienHinh($dataHinh, $thongTinSanPham->mathuvienhinh); //sua thong tin thu vien hinh tren database
            }
            // ***********Xu ly sua qua tang
            $dataQuaTang = [
                $thongTinSanPham->tensanpham, //ten san pham [0]
                NULL, //ma san pham 1 [1]
                NULL, //ma san pham 2 [2]
                NULL, //ma san pham 3 [3]
                NULL, //ma san pham 4 [4]
                NULL, //ma san pham 5 [5]
            ];
            $dem = 1;
            $quaTangSua = $request->quaTangSua;
            for ($i = 0; $i < count($quaTangSua); $i++) {
                if ($quaTangSua[$i] != NULL) {
                    for ($j = $i + 1; $j < count($quaTangSua); $j++) {
                        if ($quaTangSua[$i] == $quaTangSua[$j]) {
                            $quaTangSua[$j] = NULL;
                        }
                    }
                }
            }
            foreach ($quaTangSua as $maSanPhamQuaTang) {
                if (!empty($maSanPhamQuaTang)) {
                    $thongTinSanPhamTang = $this->sanPham->timSanPhamTheoMa($maSanPhamQuaTang);
                    if (!empty($thongTinSanPhamTang)) {
                        $dataQuaTang[$dem] = $thongTinSanPhamTang->masanpham;
                        $dem++;
                    }
                }
            }
            $this->quaTang->suaQuaTang($dataQuaTang, $thongTinSanPham->maquatang); // sua thong tin qua tang tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'S???a th??ng tin ph??? ki???n th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "th??m ph??? ki???n") { // *******************************************************************************************them phu kien
            $rules = [
                'tenSanPham' => 'required|string|max:150|min:3|unique:sanpham',
                'baoHanh' => 'required|integer|between:1,48',
                'hangSanXuat' => 'required|integer|exists:hangsanxuat,mahang',
                'tenLoaiPhuKien' => 'required|string|max:50|min:3',
                'quaTang' => 'required|array|size:5',
                'hinhSanPham' => 'required|array|between:1,5',
                'hinhSanPham.*' => 'image|dimensions:min_width=500,min_height=450,max_width=500,max_height=450',
                'moTa' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i thi???u :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'size' => ':attribute kh??ng ????ng s??? l?????ng (:size)',
                'unique' => ':attribute ???? t???n t???i',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai',
                'hinhSanPham.required' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPham.array' => 'H??nh s???n ph???m ch???n sai',
                'hinhSanPham.between' => 'H??nh s???n ph???m v?????t qu?? s??? l?????ng cho ph??p',
                'hinhSanPham.*.image' => 'H??nh s???n ph???m kh??ng ????ng ?????nh d???ng',
                'hinhSanPham.*.dimensions' => 'H??nh s???n ph???m kh??ng ????ng k??ch th?????c :min_width x :min_height'
            ];
            $attributes = [
                'tenSanPham' => 'T??n s???n ph???m',
                'baoHanh' => 'B???o h??nh',
                'hangSanXuat' => 'H??ng s???n xu???t',
                'tenLoaiPhuKien' => 'T??n lo???i ph??? ki???n',
                'quaTang' => 'Q??a t???ng',
                'moTa' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            // ***********Xu ly them qua tang
            $dataQuaTang = [
                NULL, //ma qua tang [0]
                $request->tenSanPham, //ten san pham [1]
                NULL, //ma san pham 1 [2]
                NULL, //ma san pham 2 [4]
                NULL, //ma san pham 3 [5]
                NULL, //ma san pham 4 [6]
                NULL, //ma san pham 5 [7]
            ];
            $dem = 2;
            $quaTang = $request->quaTang;
            for ($i = 0; $i < count($quaTang); $i++) { // loc ma san pham tang bi trung
                if ($quaTang[$i] != NULL) {
                    for ($j = $i + 1; $j < count($quaTang); $j++) {
                        if ($quaTang[$i] == $quaTang[$j]) {
                            $quaTang[$j] = NULL;
                        }
                    }
                }
            }
            foreach ($quaTang as $maSanPhamQuaTang) {
                if (!empty($maSanPhamQuaTang)) {
                    $thongTinSanPhamTang = $this->sanPham->timSanPhamTheoMa($maSanPhamQuaTang);
                    if (!empty($thongTinSanPhamTang)) {
                        $dataQuaTang[$dem] = $thongTinSanPhamTang->masanpham;
                        $dem++;
                    }
                }
            }

            // ***********Xu ly them thu vien hinh
            $tenHinh = [NULL, NULL, NULL, NULL, NULL];
            $dem = 0;
            if ($request->has('hinhSanPham')) {
                foreach ($request->hinhSanPham as $hinh) {
                    $tenHinh[$dem] = $request->tenSanPham . '-' . time() . '-' . $dem . '.' . $hinh->guessExtension();
                    $hinh->move(public_path('img/sanpham'), $tenHinh[$dem]);
                    $dem++;
                }
            }
            $dataHinh = [
                NULL, //ma hinh
                $request->tenSanPham,
                $tenHinh[0], //hinh 1
                $tenHinh[1], //hinh 2
                $tenHinh[2], //hinh 3
                $tenHinh[3], //hinh 4
                $tenHinh[4], //hinh 5
            ];
            // ***********Xu ly them phu kien
            $dataPhuKien = [
                NULL, //ma phu kien
                $request->tenSanPham,
                $request->tenLoaiPhuKien
            ];

            $this->quaTang->themQuaTang($dataQuaTang); //them vao database
            $thongTinQuaTang = $this->quaTang->timQuaTangTheoTenSanPham($request->tenSanPham); //tim qua tang vua them

            $this->thuVienHinh->themThuVienHinh($dataHinh); //them vao database
            $thongTinHinh = $this->thuVienHinh->timThuVienHinhTheoTenSanPham($request->tenSanPham); //tim thu vien hinh vua them

            $this->phuKien->themPhuKien($dataPhuKien); //them vao database
            $thongTinPhuKien = $this->phuKien->timPhuKienTheoTenSanPham($request->tenSanPham); //tim phu kien vua them

            // ***********Xu ly them sanpham
            $dataSanPham = [
                NULL, //ma san pham
                $request->tenSanPham,
                $request->baoHanh,
                $request->moTa,
                0, //so luong
                0, //gia nhap
                0, //gia ban
                NULL, //gia khuyen mai
                $thongTinHinh->mathuvienhinh, //ma thu vien hinh
                $request->hangSanXuat, //ma hang
                $thongTinQuaTang->maquatang, //ma qua tang
                NULL, //ma lap top
                $thongTinPhuKien->maphukien, //ma phu kien
                1 //loai san pham
                //ngaytao tu dong
            ];
            $this->sanPham->themSanPham($dataSanPham);
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m ph??? ki???n m???i th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function hangsanxuat()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.hangsanxuat', compact(
            'danhSachSanPham',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachHangSanXuat'
        ));
    }
    public function xulyhangsanxuat(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "th??m h??ng s???n xu???t") { // *******************************************************************************************them hang san xuat
            $rules = [
                'tenHang' => 'required|string|max:50|min:1',
                'loaiHang' => 'required|integer|between:0,1'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'integer' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai'
            ];
            $attributes = [
                'tenHang' => 'T??n h??ng',
                'loaiHang' => 'Lo???i h??ng'
            ];
            $request->validate($rules, $messages, $attributes);
            $tenHang = mb_strtoupper($request->tenHang, 'UTF-8');
            $thongTinHang = $this->hangSanXuat->timHangSanXuatTheoTen($tenHang);
            if (!empty($thongTinHang)) {
                if ($thongTinHang->loaihang == $request->loaiHang) {
                    return back()->with(
                        'tieudethongbao',
                        'Thao t??c th???t b???i'
                    )->with(
                        'thongbao',
                        'T??n h??ng s???n xu???t ???? t???n t???i, vui l??ng nh???p l???i!'
                    )->with(
                        'loaithongbao',
                        'danger'
                    );
                }
            }
            $dataHangSanXuat = [
                NULL, //mahang tu dong
                $tenHang,
                $request->loaiHang
            ];
            $this->hangSanXuat->themHangSanXuat($dataHangSanXuat); //them hang san xuat vao database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m h??ng s???n xu???t th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "x??a h??ng s???n xu???t") { // *******************************************************************************************xoa hang san xuat
            $rules = [
                'maHangXoa' => 'required|integer|exists:hangsanxuat,mahang|unique:sanpham,mahang'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'unique' => 'T???n t???i s???n ph???m thu???c :attribute n??y n??n kh??ng th??? x??a',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maHangXoa' => 'H??ng s???n xu???t'
            ];
            $request->validate($rules, $messages, $attributes);
            $this->hangSanXuat->xoaHangSanXuat($request->maHangXoa); //xoa hang san xuat tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m h??ng s???n xu???t th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function xemphieuxuat(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $rules = [
            'mapx' => 'required|integer|exists:phieuxuat,maphieuxuat'
        ];
        $messages = [
            'required' => ':attribute b???t bu???c nh???p',
            'exists' => ':attribute kh??ng t???n t???i',
            'integer' => ':attribute nh???p sai'
        ];
        $attributes = [
            'mapx' => 'M?? phi???u xu???t'
        ];
        $request->validate($rules, $messages, $attributes);
        $phieuXuatCanXem = $this->phieuXuat->timPhieuXuatTheoMa($request->mapx);
        $nguoiDungCanXem = $this->nguoiDung->timNguoiDungTheoMa($phieuXuatCanXem->manguoidung);
        $maGiamGiaCanXem = $this->maGiamGia->timMaGiamGiaTheoMa($phieuXuatCanXem->magiamgia);
        $danhSachChiTietPhieuXuatCanXem = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($phieuXuatCanXem->maphieuxuat);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        return view('admin.pdf.phieuxuat', compact(
            'phieuXuatCanXem',
            'nguoiDungCanXem',
            'maGiamGiaCanXem',
            'danhSachChiTietPhieuXuatCanXem',
            'danhSachSanPham'
        ));
    }
    public function inphieuxuat(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $rules = [
            'mapx' => 'required|integer|exists:phieuxuat,maphieuxuat'
        ];
        $messages = [
            'required' => ':attribute b???t bu???c nh???p',
            'exists' => ':attribute kh??ng t???n t???i',
            'integer' => ':attribute nh???p sai'
        ];
        $attributes = [
            'mapx' => 'M?? phi???u xu???t'
        ];
        $request->validate($rules, $messages, $attributes);
        $phieuXuatCanXem = $this->phieuXuat->timPhieuXuatTheoMa($request->mapx);
        $nguoiDungCanXem = $this->nguoiDung->timNguoiDungTheoMa($phieuXuatCanXem->manguoidung);
        $maGiamGiaCanXem = $this->maGiamGia->timMaGiamGiaTheoMa($phieuXuatCanXem->magiamgia);
        $danhSachChiTietPhieuXuatCanXem = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($phieuXuatCanXem->maphieuxuat);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
    	$pdf = PDF::loadView('admin.pdf.phieuxuat',compact(
            'phieuXuatCanXem',
            'nguoiDungCanXem',
            'maGiamGiaCanXem',
            'danhSachChiTietPhieuXuatCanXem',
            'danhSachSanPham'
        ));
    	return $pdf->stream('PX'.$phieuXuatCanXem->maphieuxuat.'.pdf');
    }
    public function phieuxuat()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachPhieuXuat = $this->phieuXuat->layDanhSachPhieuXuat();
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachMaGiamGia = $this->maGiamGia->layDanhSachMaGiamGia();
        return view('admin.phieuxuat', compact(
            'danhSachPhieuXuat',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachNguoiDung',
            'danhSachMaGiamGia'
        ));
    }
    public function xulyphieuxuat(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "?????i t??nh tr???ng giao h??ng") { // *******************************************************************************************doi tinh trang giao hang phieu xuat
            $rules = [
                'maPhieuXuatDoi' => 'required|integer|exists:phieuxuat,maphieuxuat'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maPhieuXuatDoi' => 'M?? phi???u xu???t'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoMa($request->maPhieuXuatDoi); //tim phieu xuat can doi
            if (!empty($thongTinPhieuXuat)) {
                $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($thongTinPhieuXuat->manguoidung);
                if ($thongTinNguoiDung->trangthai == 0) { // thong tin nguoi dung dang bi khoa
                    return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Tr???ng th??i ng?????i d??ng ??ang b??? kh??a, kh??ng th??? thao t??c!')->with('loaithongbao', 'danger');
                }
                if ($thongTinPhieuXuat->tinhtranggiaohang >= 4) $thongTinPhieuXuat->tinhtranggiaohang = 0;
                else $thongTinPhieuXuat->tinhtranggiaohang++;
                $dataPhieuXuat = [
                    $thongTinPhieuXuat->tinhtranggiaohang
                ];
                $this->phieuXuat->doiTinhTrangGiaoHangPhieuXuat($dataPhieuXuat, $thongTinPhieuXuat->maphieuxuat); //doi tinh trang giao hang phieu xuat tren database
                $danhSachChiTietPhieuXuat = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($thongTinPhieuXuat->maphieuxuat); //chinh ton kho
                if (!empty($danhSachChiTietPhieuXuat)) {
                    foreach ($danhSachChiTietPhieuXuat as $ctpx) {
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($ctpx->masanpham); //tim san pham can chinh so luong
                        if (!empty($thongTinSanPham) && $thongTinPhieuXuat->tinhtranggiaohang == 0) {
                            $dataSanPham = [
                                $thongTinSanPham->soluong + $ctpx->soluong
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                        }
                        if (!empty($thongTinSanPham) && $thongTinPhieuXuat->tinhtranggiaohang == 4) {
                            $dataSanPham = [
                                $thongTinSanPham->soluong - $ctpx->soluong
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                        }
                    }
                }
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th??nh c??ng'
                )->with(
                    'thongbao',
                    '?????i t??nh tr???ng giao h??ng phi???u xu???t th??nh c??ng'
                )->with(
                    'loaithongbao',
                    'success'
                );
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                '?????i t??nh tr???ng giao h??ng phi???u xu???t th???t b???i'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        if ($request->thaoTac == "x??a phi???u xu???t") { // *******************************************************************************************xoa phieu xuat
            $rules = [
                'maPhieuXuatXoa' => 'required|integer|exists:phieuxuat,maphieuxuat'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maPhieuXuatXoa' => 'M?? phi???u xu???t'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoMa($request->maPhieuXuatXoa); //tim phieu xuat can xoa
            if (!empty($thongTinPhieuXuat)) {
                $danhSachChiTietPhieuXuat = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($thongTinPhieuXuat->maphieuxuat); //tim chi tiet phieu xuat can xoa
                if (!empty($danhSachChiTietPhieuXuat)) {
                    foreach ($danhSachChiTietPhieuXuat as $ctpx) {
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($ctpx->masanpham); //tim san pham can chinh so luong
                        if (!empty($thongTinSanPham) && $thongTinPhieuXuat->tinhtranggiaohang == 4) {
                            $dataSanPham = [
                                $thongTinSanPham->soluong + $ctpx->soluong
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                        }
                        $this->chiTietPhieuXuat->xoaChiTietPhieuXuat($ctpx->machitietphieuxuat); //xoa chi tiet phieu xuat tren database
                    }
                }
                $this->phieuXuat->xoaPhieuXuat($thongTinPhieuXuat->maphieuxuat); //xoa phieu xuat tren database
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th??nh c??ng'
                )->with(
                    'thongbao',
                    'X??a phi???u xu???t th??nh c??ng'
                )->with(
                    'loaithongbao',
                    'success'
                );
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                'X??a phi???u xu???t th???t b???i'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        if ($request->thaoTac == "s???a phi???u xu???t") { // *******************************************************************************************sua phieu xuat
            $rules = [
                'maPhieuXuatSua' => 'required|integer|exists:phieuxuat,maphieuxuat',
                'chiTietPhieuXuat' => 'required|array',
                'chiTietPhieuXuat.*' => 'required|string|max:255|min:3',
                'soLuong' => 'required|array',
                'soLuong.*' => 'required|integer',
                'baoHanh' => 'required|array',
                'baoHanh.*' => 'required|integer',
                'donGia' => 'required|array',
                'donGia.*' => 'required|string|max:255|min:1',
                'thongTinNguoiDung' => 'required|string|max:255|min:3',
                'tongTien' => 'required|numeric',
                'daThanhToan' => 'required|string|max:255|min:1',
                'hinhThucThanhToan' => 'required|integer|between:0,2',
                'tinhTrangGiaoHang' => 'required|integer|between:0,4',
                'ghiChu' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'integer' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???'
            ];
            $attributes = [
                'maPhieuXuatSua' => 'M?? phi???u xu???t',
                'chiTietPhieuXuat' => 'Chi ti???t phi???u xu???t',
                'chiTietPhieuXuat.*' => 'Chi ti???t phi???u xu???t *',
                'soLuong' => 'S??? l?????ng',
                'soLuong.*' => 'S??? l?????ng *',
                'baoHanh' => 'B???o h??nh',
                'baoHanh.*' => 'B???o h??nh *',
                'donGia' => '????n gi??',
                'donGia.*' => '????n gi?? *',
                'thongTinNguoiDung' => 'Th??ng tin ng?????i d??ng',
                'tongTien' => 'T???ng ti???n',
                'daThanhToan' => '???? thanh to??n',
                'hinhThucThanhToan' => 'H??nh th???c thanh to??n',
                'tinhTrangGiaoHang' => 'T??nh tr???ng giao h??ng',
                'ghiChu' => 'Ghi ch??'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoMa($request->maPhieuXuatSua); //tim phieu xuat
            // ***********Xu ly phieu xuat
            $thongTinNguoiDung = explode(' | ', $request->thongTinNguoiDung);
            if (empty($thongTinNguoiDung[0]) || empty($thongTinNguoiDung[1]) || empty($thongTinNguoiDung[2])) { // thong tin nguoi dung nhap vao sai cu phap quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a th??ng tin kh??ch h??ng, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $maNguoiDung = explode('ND', $thongTinNguoiDung[0]);
            $maNguoiDung = $maNguoiDung[1];
            $hoTen = $thongTinNguoiDung[1];
            $soDienThoai = $thongTinNguoiDung[2];
            if (!is_numeric($maNguoiDung)) { // ma nguoi dung nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a th??ng tin kh??ch h??ng, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinPhieuXuat->manguoidung != $maNguoiDung) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a th??ng tin kh??ch h??ng, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($maNguoiDung);
            if (empty($thongTinNguoiDung)) { // khong tim thay nguoi dung tren database quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a th??ng tin kh??ch h??ng, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($hoTen != $thongTinNguoiDung->hoten || $soDienThoai != $thongTinNguoiDung->sodienthoai) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a th??ng tin kh??ch h??ng, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinNguoiDung->trangthai == 0) { // thong tin nguoi dung dang bi khoa
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Tr???ng th??i ng?????i d??ng ??ang b??? kh??a, kh??ng th??? thao t??c!')->with('loaithongbao', 'danger');
            }
            $soTienDaThanhToan = explode(',', $request->daThanhToan);
            $temp = "";
            foreach ($soTienDaThanhToan as $stdtt) {
                $temp = $temp . $stdtt;
            }
            $soTienDaThanhToan = $temp;
            if (!is_numeric($soTienDaThanhToan)) { // so tien da thanh toan nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if (($soTienDaThanhToan == 0 && $request->tongTien == 0) || $soTienDaThanhToan > $request->tongTien) { // phieu khong co gi nen khong lap va quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $hoTenNguoiNhan = $thongTinPhieuXuat->hotennguoinhan;
            $soDienThoaiNguoiNhan = $thongTinPhieuXuat->sodienthoainguoinhan;
            $diaChiNguoiNhan = $thongTinPhieuXuat->diachinguoinhan;
            $ghiChu = $thongTinPhieuXuat->ghichu;
            $tongTien = $thongTinPhieuXuat->tongtien;
            $tinhTrangGiaoHang = $thongTinPhieuXuat->tinhtranggiaohang;
            $hinhThucThanhToan = $thongTinPhieuXuat->hinhthucthanhtoan;
            $congNo = $thongTinPhieuXuat->congno;
            $congNoSua = $soTienDaThanhToan - $request->tongTien;
            if(!empty($thongTinPhieuXuat->magiamgia)){
                $thongTinMaGiamGia = $this->maGiamGia->timMaGiamGiaTheoMa($thongTinPhieuXuat->magiamgia); //tim ma giam gia
                if (!empty($thongTinMaGiamGia)) {
                    $congNoSua += $thongTinMaGiamGia->sotiengiam;
                } else {
                    return back()->with('thongbao', 'M?? gi???m gi?? kh??ng t???n t???i!');
                }
            }
            if (isset($request->thongTinNguoiNhanKhac)) {
                if ($request->thongTinNguoiNhanKhac == "on") {
                    $rules = [
                        'hoTenNguoiNhan' => 'required|string|max:50|min:3',
                        'soDienThoaiNguoiNhan' => 'required|numeric|digits:10',
                        'diaChiNguoiNhan' => 'required|string|max:255|min:3',
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
                        'hoTenNguoiNhan' => 'H??? t??n ng?????i nh???n',
                        'soDienThoaiNguoiNhan' => 'S??? ??i???n tho???i ng?????i nh???n',
                        'diaChiNguoiNhan' => '?????a ch??? ng?????i nh???n',
                    ];
                    $request->validate($rules, $messages, $attributes);
                    if ($request->hoTenNguoiNhan != $hoTenNguoiNhan) { //ho ten nguoi nhan vua chinh sua khac voi ho ten nguoi nhan cu
                        $hoTenNguoiNhan = $request->hoTenNguoiNhan;
                    }
                    if ($request->soDienThoaiNguoiNhan != $soDienThoaiNguoiNhan) { //sdt nguoi nhan vua chinh sua khac voi sdt nguoi nhan cu
                        $soDienThoaiNguoiNhan = $request->soDienThoaiNguoiNhan;
                    }
                    if ($request->diaChiNguoiNhan != $diaChiNguoiNhan) { //dia chi nguoi nhan vua chinh sua khac voi dia chi nguoi nhan cu
                        $diaChiNguoiNhan = $request->diaChiNguoiNhan;
                    }
                } else {
                    return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i nh???n kh??c nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                }
            } else { //neu khong tich chon giao den noi khac
                $hoTenNguoiNhan = $thongTinNguoiDung->hoten;
                $soDienThoaiNguoiNhan = $thongTinNguoiDung->sodienthoai;
                $diaChiNguoiNhan = $thongTinNguoiDung->diachi;
                //lay thong tin nguoi dung dat hang lam thong tin giao hang
            }
            if ($request->ghiChu != $ghiChu) { //ghi chu vua chinh sua khac voi ghi chu cu
                $ghiChu = $request->ghiChu;
            }
            if ($request->tongTien != $tongTien) { //tong tien vua chinh sua khac voi tong tien cu
                $tongTien = $request->tongTien;
            }
            if ($request->tinhTrangGiaoHang != $tinhTrangGiaoHang) { //tinh trang giao hang vua chinh sua khac voi tinh trang giao hang cu
                $tinhTrangGiaoHang = $request->tinhTrangGiaoHang;
            }
            if ($request->hinhThucThanhToan != $hinhThucThanhToan) { //hinh thuc thanh toan vua chinh sua khac voi hinh thuc thanh toan cu
                $hinhThucThanhToan = $request->hinhThucThanhToan;
            }
            if ($congNoSua != $congNo) { //ghi chu vua chinh sua khac voi ghi chu cu
                $congNo = $congNoSua;
            }
            $dataPhieuXuat = [
                $hoTenNguoiNhan,
                $soDienThoaiNguoiNhan,
                $diaChiNguoiNhan,
                $ghiChu,
                $tongTien,
                $tinhTrangGiaoHang,
                $hinhThucThanhToan,
                $congNo
            ];
            $this->phieuXuat->suaPhieuXuat($dataPhieuXuat, $thongTinPhieuXuat->maphieuxuat); //sua phieu xuat tren database
            $danhSachChiTietPhieuXuat = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($thongTinPhieuXuat->maphieuxuat); //tim danh sach chi tiet phieu xuat
            // ***********Xu ly them chi tiet phieu xuat
            if (!empty($request->chiTietPhieuXuat)) {
                for ($i = 0; $i < count($request->chiTietPhieuXuat); $i++) {
                    if (!empty($request->chiTietPhieuXuat[$i]) && $request->soLuong[$i] > 0 && $request->donGia[$i] >= 0 && $request->baoHanh[$i] >= 0) {
                        $thongTinSanPham = explode(' | ', $request->chiTietPhieuXuat[$i]);
                        if (empty($thongTinSanPham[0]) || empty($thongTinSanPham[1])) { // thong tin san pham xuat  sai cu phap quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $maSanPham = explode('SP', $thongTinSanPham[0]);
                        $maSanPham = $maSanPham[1];
                        $tenSanPham = $thongTinSanPham[1];
                        if (!is_numeric($maSanPham)) { // ma san pham xuat  khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($maSanPham);
                        if (empty($thongTinSanPham)) { // khong tim thay san pham tren database quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        if ($tenSanPham != $thongTinSanPham->tensanpham) { // thong tin san pham khong khop va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $soLuongXuat = $request->soLuong[$i];
                        $baoHanhXuat = $request->baoHanh[$i];
                        $donGiaXuat = explode(',', $request->donGia[$i]);
                        $temp = "";
                        foreach ($donGiaXuat as $dgx) {
                            $temp = $temp . $dgx;
                        }
                        $donGiaXuat = $temp;
                        if (!is_numeric($donGiaXuat)) { // so tien don gia xuat  khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ????n gi?? nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $dataChiTietPhieuXuat = [
                            NULL, //machitietphieuxuat tu dong
                            $thongTinPhieuXuat->maphieuxuat,
                            $thongTinSanPham->masanpham,
                            $baoHanhXuat,
                            $soLuongXuat,
                            $donGiaXuat
                        ];
                        $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
                        // Xu ly so luong san pham
                        if ($request->tinhTrangGiaoHang == 4) { //phieu xuat da giao hang moi tru vao ton kho
                            $dataSanPham = [
                                $thongTinSanPham->soluong - $soLuongXuat, //tru so luong vua xuat vao ton kho
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //them so luong ton kho va chinh gia database
                        }
                    }
                }
                if (!empty($danhSachChiTietPhieuXuat)) {
                    foreach ($danhSachChiTietPhieuXuat as $ctpx) {
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($ctpx->masanpham); //tim san pham can chinh so luong
                        if (!empty($thongTinSanPham) && $thongTinPhieuXuat->tinhtranggiaohang == 4) {
                            $dataSanPham = [
                                $thongTinSanPham->soluong + $ctpx->soluong
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                        }
                        $this->chiTietPhieuXuat->xoaChiTietPhieuXuat($ctpx->machitietphieuxuat); //xoa chi tiet phieu xuat tren database
                    }
                }
                return redirect()->route('phieuxuat')->with('tieudethongbao', 'Thao t??c th??nh c??ng')->with('thongbao', 'S???a th??ng tin phi???u xu???t th??nh c??ng')->with('loaithongbao', 'success');
            }
            return redirect()->route('phieuxuat')->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S???a th??ng tin phi???u xu???t th???t b???i')->with('loaithongbao', 'danger');
        }
        if ($request->thaoTac == "th??m phi???u xu???t") { // *******************************************************************************************them phieu xuat
            $rules = [
                'chiTietPhieuXuat' => 'required|array',
                'chiTietPhieuXuat.*' => 'required|string|max:255|min:3',
                'soLuong' => 'required|array',
                'soLuong.*' => 'required|integer',
                'baoHanh' => 'required|array',
                'baoHanh.*' => 'required|integer',
                'donGia' => 'required|array',
                'donGia.*' => 'required|string|max:255|min:1',
                'thongTinNguoiDung' => 'required|string|max:255|min:3',
                'tongTien' => 'required|numeric',
                'daThanhToan' => 'required|string|max:255|min:1',
                'hinhThucThanhToan' => 'required|integer|between:0,2',
                'tinhTrangGiaoHang' => 'required|integer|between:0,4',
                'ghiChu' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'integer' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???'
            ];
            $attributes = [
                'chiTietPhieuXuat' => 'Chi ti???t phi???u xu???t',
                'chiTietPhieuXuat.*' => 'Chi ti???t phi???u xu???t *',
                'soLuong' => 'S??? l?????ng',
                'soLuong.*' => 'S??? l?????ng *',
                'baoHanh' => 'B???o h??nh',
                'baoHanh.*' => 'B???o h??nh *',
                'donGia' => '????n gi??',
                'donGia.*' => '????n gi?? *',
                'thongTinNguoiDung' => 'Th??ng tin ng?????i d??ng',
                'tongTien' => 'T???ng ti???n',
                'daThanhToan' => '???? thanh to??n',
                'hinhThucThanhToan' => 'H??nh th???c thanh to??n',
                'tinhTrangGiaoHang' => 'T??nh tr???ng giao h??ng',
                'ghiChu' => 'Ghi ch??'
            ];
            $request->validate($rules, $messages, $attributes);
            // ***********Xu ly them phieu xuat
            $thongTinNguoiDung = explode(' | ', $request->thongTinNguoiDung);
            if (empty($thongTinNguoiDung[0]) || empty($thongTinNguoiDung[1]) || empty($thongTinNguoiDung[2])) { // thong tin nguoi dung nhap vao sai cu phap quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $maNguoiDung = explode('ND', $thongTinNguoiDung[0]);
            $maNguoiDung = $maNguoiDung[1];
            $hoTen = $thongTinNguoiDung[1];
            $soDienThoai = $thongTinNguoiDung[2];
            if (!is_numeric($maNguoiDung)) { // ma nguoi dung nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($maNguoiDung);
            if (empty($thongTinNguoiDung)) { // khong tim thay nguoi dung tren database quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($hoTen != $thongTinNguoiDung->hoten || $soDienThoai != $thongTinNguoiDung->sodienthoai) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinNguoiDung->trangthai == 0) { // thong tin nguoi dung dang bi khoa
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Tr???ng th??i ng?????i d??ng ??ang b??? kh??a, kh??ng th??? thao t??c!')->with('loaithongbao', 'danger');
            }
            $soTienDaThanhToan = explode(',', $request->daThanhToan);
            $temp = "";
            foreach ($soTienDaThanhToan as $stdtt) {
                $temp = $temp . $stdtt;
            }
            $soTienDaThanhToan = $temp;
            if (!is_numeric($soTienDaThanhToan)) { // so tien da thanh toan nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if (($soTienDaThanhToan == 0 && $request->tongTien == 0) || $soTienDaThanhToan > $request->tongTien) { // phieu khong co gi nen khong lap va quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $congNo = $soTienDaThanhToan - $request->tongTien;
            $ngayTao = date("Y-m-d H:i:s");
            $dataPhieuXuat = [
                NULL, //maphieuxuat tu dong
                $thongTinNguoiDung->hoten,    // hotennguoinhan,
                $thongTinNguoiDung->sodienthoai,    // sodienthoainguoinhan,
                $thongTinNguoiDung->diachi,    // diachinguoinhan,
                $thongTinNguoiDung->manguoidung,
                NULL,    // magiamgia,
                $request->ghiChu,
                $request->tongTien,
                $request->tinhTrangGiaoHang,    // tinhtranggiaohang,  	0 l?? ???? h???y, 1 l?? ch??? x??c nh???n, 2 l?? ??ang chu???n b??? h??ng, 3 l?? ??ang giao, 4 l?? ???? giao th??nh c??ng
                $request->hinhThucThanhToan,    // hinhthucthanhtoan,   0 l?? ti???n m???t, 1 l?? chuy???n kho???n, 2 l?? atm qua vpn
                $congNo,    // congno, 0 l?? ???? thanh to??n, !=0 l?? c??ng n???
                $ngayTao    // ngaytao
            ];
            if (isset($request->thongTinNguoiNhanKhac)) {
                if ($request->thongTinNguoiNhanKhac == "on") {
                    $rules = [
                        'hoTenNguoiNhan' => 'required|string|max:50|min:3',
                        'soDienThoaiNguoiNhan' => 'required|numeric|digits:10',
                        'diaChiNguoiNhan' => 'required|string|max:255|min:3',
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
                        'hoTenNguoiNhan' => 'H??? t??n ng?????i nh???n',
                        'soDienThoaiNguoiNhan' => 'S??? ??i???n tho???i ng?????i nh???n',
                        'diaChiNguoiNhan' => '?????a ch??? ng?????i nh???n',
                    ];
                    $request->validate($rules, $messages, $attributes);
                    $dataPhieuXuat = [
                        NULL, //maphieuxuat tu dong
                        $request->hoTenNguoiNhan,    // hotennguoinhan,
                        $request->soDienThoaiNguoiNhan,    // sodienthoainguoinhan,
                        $request->diaChiNguoiNhan,    // diachinguoinhan,
                        $thongTinNguoiDung->manguoidung,
                        NULL,    // magiamgia,
                        $request->ghiChu,
                        $request->tongTien,
                        $request->tinhTrangGiaoHang,    // tinhtranggiaohang,  	0 l?? ???? h???y, 1 l?? ch??? x??c nh???n, 2 l?? ??ang chu???n b??? h??ng, 3 l?? ??ang giao, 4 l?? ???? giao th??nh c??ng
                        $request->hinhThucThanhToan,    // hinhthucthanhtoan,   0 l?? ti???n m???t, 1 l?? chuy???n kho???n, 2 l?? atm qua vpn
                        $congNo,    // congno, 0 l?? ???? thanh to??n, !=0 l?? c??ng n???
                        $ngayTao    // ngaytao
                    ];
                } else {
                    return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i nh???n kh??c nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                }
            }
            $this->phieuXuat->themPhieuXuat($dataPhieuXuat); //them phieu xuat vao database
            $thongTinPhieuXuat = $this->phieuXuat->timPhieuXuatTheoNgayTao($ngayTao); //tim phieu xuat vua them
            // ***********Xu ly them chi tiet phieu xuat
            if (!empty($request->chiTietPhieuXuat)) {
                for ($i = 0; $i < count($request->chiTietPhieuXuat); $i++) {
                    if (!empty($request->chiTietPhieuXuat[$i]) && $request->soLuong[$i] > 0 && $request->donGia[$i] >= 0 && $request->baoHanh[$i] >= 0) {
                        $thongTinSanPham = explode(' | ', $request->chiTietPhieuXuat[$i]);
                        if (empty($thongTinSanPham[0]) || empty($thongTinSanPham[1])) { // thong tin san pham xuat  sai cu phap quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $maSanPham = explode('SP', $thongTinSanPham[0]);
                        $maSanPham = $maSanPham[1];
                        $tenSanPham = $thongTinSanPham[1];
                        if (!is_numeric($maSanPham)) { // ma san pham xuat  khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($maSanPham);
                        if (empty($thongTinSanPham)) { // khong tim thay san pham tren database quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        if ($tenSanPham != $thongTinSanPham->tensanpham) { // thong tin san pham khong khop va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $soLuongXuat = $request->soLuong[$i];
                        $baoHanhXuat = $request->baoHanh[$i];
                        $donGiaXuat = explode(',', $request->donGia[$i]);
                        $temp = "";
                        foreach ($donGiaXuat as $dgx) {
                            $temp = $temp . $dgx;
                        }
                        $donGiaXuat = $temp;
                        if (!is_numeric($donGiaXuat)) { // so tien don gia xuat  khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ????n gi?? nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $dataChiTietPhieuXuat = [
                            NULL, //machitietphieuxuat tu dong
                            $thongTinPhieuXuat->maphieuxuat,
                            $thongTinSanPham->masanpham,
                            $baoHanhXuat,
                            $soLuongXuat,
                            $donGiaXuat
                        ];
                        $this->chiTietPhieuXuat->themChiTietPhieuXuat($dataChiTietPhieuXuat); //them chi tiet phieu xuat vao database
                        // Xu ly so luong san pham
                        if ($request->tinhTrangGiaoHang == 4) { //phieu xuat da giao hang moi tru vao ton kho
                            $dataSanPham = [
                                $thongTinSanPham->soluong - $soLuongXuat, //tru so luong vua xuat vao ton kho
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //them so luong ton kho va chinh gia database
                        }
                    }
                }
            }
            return redirect()->route('phieuxuat')->with('tieudethongbao', 'Thao t??c th??nh c??ng')->with('thongbao', 'L???p phi???u xu???t th??nh c??ng')->with('loaithongbao', 'success');
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function themphieuxuat(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPhamChoPhieu();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachHangSanXuatLaptop = []; // loc lai danh sach theo loai hang san xuat laptop can xem
        $danhSachHangSanXuatPhuKien = [];
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 0) {
                $danhSachHangSanXuatLaptop = array_merge($danhSachHangSanXuatLaptop, [$hangSanXuat]);
            }
            if ($hangSanXuat->loaihang == 1) {
                $danhSachHangSanXuatPhuKien = array_merge($danhSachHangSanXuatPhuKien, [$hangSanXuat]);
            }
        }

        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachKhachHang = []; // loc lai danh sach thong tin nha cung cap gom nguoi dung la khach hang hoac doi tac va co trang thai dang hoat dong
        foreach ($danhSachNguoiDung as $nguoiDung) {
            if (($nguoiDung->loainguoidung == 0 || $nguoiDung->loainguoidung == 1) && $nguoiDung->trangthai == 1) {
                $danhSachKhachHang = array_merge($danhSachKhachHang, [$nguoiDung]);
            }
        }
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.themphieuxuat', compact(
            'danhSachSanPham',
            'danhSachHangSanXuatLaptop',
            'danhSachHangSanXuatPhuKien',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachKhachHang'
        ));
    }
    public function suaphieuxuat(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $rules = [
            'id' => 'required|integer|exists:phieuxuat,maphieuxuat'
        ];
        $messages = [
            'required' => ':attribute b???t bu???c nh???p',
            'exists' => ':attribute kh??ng t???n t???i',
            'integer' => ':attribute nh???p sai'
        ];
        $attributes = [
            'id' => 'M?? phi???u xu???t'
        ];
        $request->validate($rules, $messages, $attributes);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPhamChoPhieu();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachHangSanXuatLaptop = []; // loc lai danh sach theo loai hang san xuat laptop can xem
        $danhSachHangSanXuatPhuKien = [];
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 0) {
                $danhSachHangSanXuatLaptop = array_merge($danhSachHangSanXuatLaptop, [$hangSanXuat]);
            }
            if ($hangSanXuat->loaihang == 1) {
                $danhSachHangSanXuatPhuKien = array_merge($danhSachHangSanXuatPhuKien, [$hangSanXuat]);
            }
        }
        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachKhachHang = []; // loc lai danh sach thong tin nha cung cap gom nguoi dung la khach hang hoac doi tac va co trang thai la dang hoat dong
        foreach ($danhSachNguoiDung as $nguoiDung) {
            if (($nguoiDung->loainguoidung == 0 || $nguoiDung->loainguoidung == 1) && $nguoiDung->trangthai == 1) {
                $danhSachKhachHang = array_merge($danhSachKhachHang, [$nguoiDung]);
            }
        }
        $phieuXuatCanXem = $this->phieuXuat->timPhieuXuatTheoMa($request->id);
        $nguoiDungCanXem = $this->nguoiDung->timNguoiDungTheoMa($phieuXuatCanXem->manguoidung);
        $maGiamGiaCanXem = $this->maGiamGia->timMaGiamGiaTheoMa($phieuXuatCanXem->magiamgia);
        $danhSachChiTietPhieuXuatCanXem = $this->chiTietPhieuXuat->timDanhSachChiTietPhieuXuatTheoMaPhieuXuat($request->id);
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.suaphieuxuat', compact(
            'phieuXuatCanXem',
            'nguoiDungCanXem',
            'maGiamGiaCanXem',
            'danhSachChiTietPhieuXuatCanXem',
            'danhSachSanPham',
            'danhSachHangSanXuatLaptop',
            'danhSachHangSanXuatPhuKien',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachKhachHang'
        ));
    }
    public function inphieunhap(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $rules = [
            'mapn' => 'required|integer|exists:phieunhap,maphieunhap'
        ];
        $messages = [
            'required' => ':attribute b???t bu???c nh???p',
            'exists' => ':attribute kh??ng t???n t???i',
            'integer' => ':attribute nh???p sai'
        ];
        $attributes = [
            'mapn' => 'M?? phi???u nh???p'
        ];
        $request->validate($rules, $messages, $attributes);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $phieuNhapCanXem = $this->phieuNhap->timPhieuNhapTheoMa($request->mapn);
        $nguoiDungCanXem = $this->nguoiDung->timNguoiDungTheoMa($phieuNhapCanXem->manguoidung);
        $danhSachChiTietPhieuNhapCanXem = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaPhieuNhap($phieuNhapCanXem->maphieunhap);
    	$pdf = PDF::loadView('admin.pdf.phieunhap',compact(
            'phieuNhapCanXem',
            'nguoiDungCanXem',
            'danhSachChiTietPhieuNhapCanXem',
            'danhSachSanPham'
        ));
    	return $pdf->stream('PN'.$phieuNhapCanXem->maphieunhap.'.pdf');
    }
    public function phieunhap()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachPhieuNhap = $this->phieuNhap->layDanhSachPhieuNhap();
        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.phieunhap', compact(
            'danhSachPhieuNhap',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachNguoiDung'
        ));
    }
    public function xulyphieunhap(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "x??a phi???u nh???p") { // *******************************************************************************************xoa phieu nhap
            $rules = [
                'maPhieuNhapXoa' => 'required|integer|exists:phieunhap,maphieunhap'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute ???? nh???p sai'
            ];
            $attributes = [
                'maPhieuNhapXoa' => 'M?? phi???u nh???p'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinPhieuNhap = $this->phieuNhap->timPhieuNhapTheoMa($request->maPhieuNhapXoa); //tim phieu nhap can xoa
            if (!empty($thongTinPhieuNhap)) {
                $danhSachChiTietPhieuNhap = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaPhieuNhap($thongTinPhieuNhap->maphieunhap); //tim chi tiet phieu nhap can xoa
                if (!empty($danhSachChiTietPhieuNhap)) {
                    foreach ($danhSachChiTietPhieuNhap as $ctpn) {
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($ctpn->masanpham); //tim san pham can chinh so luong
                        if (!empty($thongTinSanPham)) {
                            $dataSanPham = [
                                $thongTinSanPham->soluong - $ctpn->soluong
                            ];
                            $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                            $this->chiTietPhieuNhap->xoaChiTietPhieuNhap($ctpn->machitietphieunhap); //xoa chi tiet phieu nhap tren database
                        }
                    }
                }
                $this->phieuNhap->xoaPhieuNhap($thongTinPhieuNhap->maphieunhap); //xoa phieu nhap tren database
                return back()->with(
                    'tieudethongbao',
                    'Thao t??c th??nh c??ng'
                )->with(
                    'thongbao',
                    'X??a phi???u nh???p th??nh c??ng'
                )->with(
                    'loaithongbao',
                    'success'
                );
            }
            return back()->with(
                'tieudethongbao',
                'Thao t??c th???t b???i'
            )->with(
                'thongbao',
                'X??a phi???u nh???p th???t b???i'
            )->with(
                'loaithongbao',
                'danger'
            );
        }
        if ($request->thaoTac == "s???a phi???u nh???p") { // *******************************************************************************************sua phieu nhap
            $rules = [
                'maPhieuNhapSua' => 'required|integer|exists:phieunhap,maphieunhap',
                'chiTietPhieuNhap' => 'array',
                'chiTietPhieuNhap.*' => 'required|string|max:255|min:3',
                'soLuong' => 'array',
                'soLuong.*' => 'required|integer',
                'donGia' => 'array',
                'donGia.*' => 'required|string|max:255|min:1',
                'thongTinNguoiDung' => 'required|string|max:255|min:3',
                'ghiChu' => 'max:255',
                'tongTien' => 'required|numeric',
                'daThanhToan' => 'required|string|max:255|min:1'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i thi???u :max k?? t???',
                'integer' => ':attribute nh???p sai',
                'numeric' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai'
            ];
            $attributes = [
                'chiTietPhieuNhap' => 'Chi ti???t phi???u nh???p',
                'chiTietPhieuNhap.*' => 'Chi ti???t phi???u nh???p *',
                'soLuong' => 'S??? l?????ng',
                'soLuong.*' => 'S??? l?????ng *',
                'donGia' => '????n gi??',
                'donGia.*' => '????n gi?? *',
                'thongTinNguoiDung' => 'Th??ng tin ng?????i d??ng',
                'ghiChu' => 'Ghi ch??',
                'tongTien' => 'T???ng ti???n',
                'daThanhToan' => '???? thanh to??n'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinPhieuNhap = $this->phieuNhap->timPhieuNhapTheoMa($request->maPhieuNhapSua); //tim phieu nhap
            // ***********Xu ly phieu nhap
            $thongTinNguoiDung = explode(' | ', $request->thongTinNguoiDung);
            if (empty($thongTinNguoiDung[0]) || empty($thongTinNguoiDung[1]) || empty($thongTinNguoiDung[2])) { // thong tin nguoi dung nhap vao sai cu phap quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a nh?? cung c???p, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $maNguoiDung = explode('ND', $thongTinNguoiDung[0]);
            $maNguoiDung = $maNguoiDung[1];
            $hoTen = $thongTinNguoiDung[1];
            $soDienThoai = $thongTinNguoiDung[2];

            if (!is_numeric($maNguoiDung)) { // ma nguoi dung nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a nh?? cung c???p, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinPhieuNhap->manguoidung != $maNguoiDung) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a nh?? cung c???p, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($maNguoiDung);
            if (empty($thongTinNguoiDung)) { // khong tim thay nguoi dung tren database quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a nh?? cung c???p, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($hoTen != $thongTinNguoiDung->hoten || $soDienThoai != $thongTinNguoiDung->sodienthoai) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Kh??ng th??? ch???nh s???a nh?? cung c???p, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinNguoiDung->trangthai == 0) { // thong tin nguoi dung dang bi khoa
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Tr???ng th??i ng?????i d??ng ??ang b??? kh??a, kh??ng th??? thao t??c!')->with('loaithongbao', 'danger');
            }
            $soTienDaThanhToan = explode(',', $request->daThanhToan);
            $temp = "";
            foreach ($soTienDaThanhToan as $stdtt) {
                $temp = $temp . $stdtt;
            }
            $soTienDaThanhToan = $temp;
            if (!is_numeric($soTienDaThanhToan)) { // so tien da thanh toan nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $ghiChu = $thongTinPhieuNhap->ghichu;
            $tongTien = $thongTinPhieuNhap->tongtien;
            $congNo = $thongTinPhieuNhap->congno;
            $congNoSua = $soTienDaThanhToan - $request->tongTien;
            if ($request->ghiChu != $ghiChu) { //ghi chu vua chinh sua khac voi ghi chu cu
                $ghiChu = $request->ghiChu;
            }
            if ($request->tongTien != $tongTien) { //tong tien vua chinh sua khac voi tong tien cu
                $tongTien = $request->tongTien;
            }
            if ($congNoSua != $congNo) { //ghi chu vua chinh sua khac voi ghi chu cu
                $congNo = $congNoSua;
            }
            $dataPhieuNhap = [
                $ghiChu,
                $tongTien,
                $congNo
            ];
            $this->phieuNhap->suaPhieuNhap($dataPhieuNhap, $thongTinPhieuNhap->maphieunhap); //sua phieu nhap tren database
            $danhSachChiTietPhieuNhap = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaPhieuNhap($thongTinPhieuNhap->maphieunhap); //tim danh sach chi tiet phieu nhap
            // ***********Xu ly chi tiet phieu nhap
            if (!empty($request->chiTietPhieuNhap)) {
                for ($i = 0; $i < count($request->chiTietPhieuNhap); $i++) {
                    if (!empty($request->chiTietPhieuNhap[$i]) && !empty($request->soLuong[$i]) && $request->donGia[$i] >= 0) {
                        $thongTinSanPham = explode(' | ', $request->chiTietPhieuNhap[$i]);
                        if (empty($thongTinSanPham[0]) || empty($thongTinSanPham[1])) { // thong tin san pham nhap vao sai cu phap quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $maSanPham = explode('SP', $thongTinSanPham[0]);
                        $maSanPham = $maSanPham[1];
                        $tenSanPham = $thongTinSanPham[1];
                        if (!is_numeric($maSanPham)) { // ma san pham nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($maSanPham);
                        if (empty($thongTinSanPham)) { // khong tim thay san pham tren database quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        if ($tenSanPham != $thongTinSanPham->tensanpham) { // thong tin san pham khong khop va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $soLuongNhap = $request->soLuong[$i];
                        $donGiaNhap = explode(',', $request->donGia[$i]);
                        $temp = "";
                        foreach ($donGiaNhap as $dgn) {
                            $temp = $temp . $dgn;
                        }
                        $donGiaNhap = $temp;
                        if (!is_numeric($donGiaNhap)) { // so tien don gia nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ????n gi?? nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }

                        $dataChiTietPhieuNhap = [
                            NULL, //machitietphieunhap tu dong
                            $thongTinPhieuNhap->maphieunhap,
                            $thongTinSanPham->masanpham,
                            $soLuongNhap,
                            $donGiaNhap
                        ];
                        $this->chiTietPhieuNhap->themChiTietPhieuNhap($dataChiTietPhieuNhap); //them chi tiet phieu nhap vao database
                        // Xu ly so luong va gia san pham
                        $giaNhap = 0;
                        if ($thongTinSanPham->gianhap > $donGiaNhap) { //gia nhap moi cu hon gia nhap moi thi lay gia nhap cu
                            $giaNhap = $thongTinSanPham->gianhap;
                        } else {
                            $giaNhap = $donGiaNhap;
                        }

                        $giaBan = 0;
                        if ($giaNhap >= $thongTinSanPham->giaban) { //gia nhap lon hon gia ban cu sua lai gia ban moi bang gia nhap + them loi 30% tren gia nhap
                            $giaBan = $giaNhap * (1 + 30 / 100);
                        } else {
                            $giaBan = $thongTinSanPham->giaban;
                        }

                        $giaKhuyenMai = NULL;
                        if (!empty($thongTinSanPham->giakhuyenmai)) {
                            if ($giaNhap >= $thongTinSanPham->giakhuyenmai) { //gia nhap lon hon gia khuyen mai cu sua lai bo luon gia khuyen mai
                                $giaKhuyenMai = NULL;
                            } else {
                                $giaKhuyenMai = $thongTinSanPham->giakhuyenmai;
                            }
                        }
                        $dataSanPham = [
                            $thongTinSanPham->soluong + $soLuongNhap, //them so luong vua nhap vao ton kho
                            $giaNhap,
                            $giaBan,
                            $giaKhuyenMai
                        ];
                        $this->sanPham->nhapHang($dataSanPham, $thongTinSanPham->masanpham); //them so luong ton kho va chinh gia database
                    }
                }
            }
            if (!empty($danhSachChiTietPhieuNhap)) {
                foreach ($danhSachChiTietPhieuNhap as $ctpn) {
                    $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($ctpn->masanpham); //tim san pham can chinh so luong
                    if (!empty($thongTinSanPham)) {
                        $dataSanPham = [
                            $thongTinSanPham->soluong - $ctpn->soluong
                        ];
                        $this->sanPham->suaSoLuong($dataSanPham, $thongTinSanPham->masanpham); //chinh so luong ton kho san pham tren database
                        $this->chiTietPhieuNhap->xoaChiTietPhieuNhap($ctpn->machitietphieunhap); //xoa chi tiet phieu nhap tren database
                    }
                }
            }
            return redirect()->route('phieunhap')->with('tieudethongbao', 'Thao t??c th??nh c??ng')->with('thongbao', 'S???a th??ng tin phi???u nh???p th??nh c??ng')->with('loaithongbao', 'success');
        }
        if ($request->thaoTac == "th??m phi???u nh???p") { // *******************************************************************************************them phieu nhap
            $rules = [
                'chiTietPhieuNhap' => 'array',
                'chiTietPhieuNhap.*' => 'required|string|max:255|min:3',
                'soLuong' => 'array',
                'soLuong.*' => 'required|integer',
                'donGia' => 'array',
                'donGia.*' => 'required|string|max:255|min:1',
                'thongTinNguoiDung' => 'required|string|max:255|min:3',
                'ghiChu' => 'max:255',
                'tongTien' => 'required|numeric',
                'daThanhToan' => 'required|string|max:255|min:1'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute nh???p sai',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i thi???u :max k?? t???',
                'integer' => ':attribute nh???p sai',
                'numeric' => ':attribute nh???p sai',
                'array' => ':attribute nh???p sai'
            ];
            $attributes = [
                'chiTietPhieuNhap' => 'Chi ti???t phi???u nh???p',
                'chiTietPhieuNhap.*' => 'Chi ti???t phi???u nh???p *',
                'soLuong' => 'S??? l?????ng',
                'soLuong.*' => 'S??? l?????ng *',
                'donGia' => '????n gi??',
                'donGia.*' => '????n gi?? *',
                'thongTinNguoiDung' => 'Th??ng tin ng?????i d??ng',
                'ghiChu' => 'Ghi ch??',
                'tongTien' => 'T???ng ti???n',
                'daThanhToan' => '???? thanh to??n'
            ];
            $request->validate($rules, $messages, $attributes);
            // ***********Xu ly them phieu nhap
            $thongTinNguoiDung = explode(' | ', $request->thongTinNguoiDung);
            if (empty($thongTinNguoiDung[0]) || empty($thongTinNguoiDung[1]) || empty($thongTinNguoiDung[2])) { // thong tin nguoi dung nhap vao sai cu phap quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $maNguoiDung = explode('ND', $thongTinNguoiDung[0]);
            $maNguoiDung = $maNguoiDung[1];
            $hoTen = $thongTinNguoiDung[1];
            $soDienThoai = $thongTinNguoiDung[2];

            if (!is_numeric($maNguoiDung)) { // ma nguoi dung nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($maNguoiDung);
            if (empty($thongTinNguoiDung)) { // khong tim thay nguoi dung tren database quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($hoTen != $thongTinNguoiDung->hoten || $soDienThoai != $thongTinNguoiDung->sodienthoai) { // thong tin nguoi dung khong khop va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin ng?????i d??ng kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($thongTinNguoiDung->trangthai == 0) { // thong tin nguoi dung dang bi khoa
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Tr???ng th??i ng?????i d??ng ??ang b??? kh??a, kh??ng th??? thao t??c!')->with('loaithongbao', 'danger');
            }
            $soTienDaThanhToan = explode(',', $request->daThanhToan);
            $temp = "";
            foreach ($soTienDaThanhToan as $stdtt) {
                $temp = $temp . $stdtt;
            }
            $soTienDaThanhToan = $temp;
            if (!is_numeric($soTienDaThanhToan)) { // so tien da thanh toan nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            if ($soTienDaThanhToan == 0 && $request->tongTien == 0) { // phieu khong co gi nen khong lap va quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ???? thanh to??n nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $congNo = $soTienDaThanhToan - $request->tongTien;
            $ngayTao = date("Y-m-d H:i:s");

            $dataPhieuNhap = [
                NULL, //maphieunhap tu dong
                $thongTinNguoiDung->manguoidung,
                $request->ghiChu,
                $request->tongTien,
                $congNo,
                $ngayTao
            ];
            $this->phieuNhap->themPhieuNhap($dataPhieuNhap); //them phieu nhap vao database
            $thongTinPhieuNhap = $this->phieuNhap->timPhieuNhapTheoNgayTao($ngayTao); //tim qua tang vua them
            // ***********Xu ly them chi tiet phieu nhap
            if (!empty($request->chiTietPhieuNhap)) {
                for ($i = 0; $i < count($request->chiTietPhieuNhap); $i++) {
                    if (!empty($request->chiTietPhieuNhap[$i]) && !empty($request->soLuong[$i]) && $request->donGia[$i] >= 0) {
                        $thongTinSanPham = explode(' | ', $request->chiTietPhieuNhap[$i]);
                        if (empty($thongTinSanPham[0]) || empty($thongTinSanPham[1])) { // thong tin san pham nhap vao sai cu phap quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $maSanPham = explode('SP', $thongTinSanPham[0]);
                        $maSanPham = $maSanPham[1];
                        $tenSanPham = $thongTinSanPham[1];
                        if (!is_numeric($maSanPham)) { // ma san pham nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $thongTinSanPham = $this->sanPham->timSanPhamTheoMa($maSanPham);
                        if (empty($thongTinSanPham)) { // khong tim thay san pham tren database quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        if ($tenSanPham != $thongTinSanPham->tensanpham) { // thong tin san pham khong khop va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th??ng tin s???n ph???m kh??ng t???n t???i, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $soLuongNhap = $request->soLuong[$i];
                        $donGiaNhap = explode(',', $request->donGia[$i]);
                        $temp = "";
                        foreach ($donGiaNhap as $dgn) {
                            $temp = $temp . $dgn;
                        }
                        $donGiaNhap = $temp;
                        if (!is_numeric($donGiaNhap)) { // so tien don gia nhap vao khong phai ky tu so quay lai trang truoc va bao loi
                            return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n ????n gi?? nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                        }
                        $dataChiTietPhieuNhap = [
                            NULL, //machitietphieunhap tu dong
                            $thongTinPhieuNhap->maphieunhap,
                            $thongTinSanPham->masanpham,
                            $soLuongNhap,
                            $donGiaNhap
                        ];
                        $this->chiTietPhieuNhap->themChiTietPhieuNhap($dataChiTietPhieuNhap); //them chi tiet phieu nhap vao database
                        // Xu ly so luong va gia san pham
                        $giaNhap = 0;
                        if ($thongTinSanPham->gianhap > $donGiaNhap) { //gia nhap moi cu hon gia nhap moi thi lay gia nhap cu
                            $giaNhap = $thongTinSanPham->gianhap;
                        } else {
                            $giaNhap = $donGiaNhap;
                        }

                        $giaBan = 0;
                        if ($giaNhap >= $thongTinSanPham->giaban) { //gia nhap lon hon gia ban cu sua lai gia ban moi bang gia nhap + them loi 30% tren gia nhap
                            $giaBan = $giaNhap * (1 + 30 / 100);
                        } else {
                            $giaBan = $thongTinSanPham->giaban;
                        }

                        $giaKhuyenMai = NULL;
                        if (!empty($thongTinSanPham->giakhuyenmai)) {
                            if ($giaNhap >= $thongTinSanPham->giakhuyenmai) { //gia nhap lon hon gia khuyen mai cu sua lai bo luon gia khuyen mai
                                $giaKhuyenMai = NULL;
                            } else {
                                $giaKhuyenMai = $thongTinSanPham->giakhuyenmai;
                            }
                        }
                        $dataSanPham = [
                            $thongTinSanPham->soluong + $soLuongNhap, //them so luong vua nhap vao ton kho
                            $giaNhap,
                            $giaBan,
                            $giaKhuyenMai
                        ];
                        $this->sanPham->nhapHang($dataSanPham, $thongTinSanPham->masanpham); //them so luong ton kho va chinh gia database
                    }
                }
            }
            return redirect()->route('phieunhap')->with('tieudethongbao', 'Thao t??c th??nh c??ng')->with('thongbao', 'L???p phi???u nh???p th??nh c??ng')->with('loaithongbao', 'success');
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function themphieunhap()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachHangSanXuatLaptop = []; // loc lai danh sach theo loai hang san xuat laptop can xem
        $danhSachHangSanXuatPhuKien = [];
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 0) {
                $danhSachHangSanXuatLaptop = array_merge($danhSachHangSanXuatLaptop, [$hangSanXuat]);
            }
            if ($hangSanXuat->loaihang == 1) {
                $danhSachHangSanXuatPhuKien = array_merge($danhSachHangSanXuatPhuKien, [$hangSanXuat]);
            }
        }

        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachNhaCungCap = []; // loc lai danh sach thong tin nha cung cap gom nguoi dung la khach hang hoac doi tac va co trang thai la dang hoat dong
        foreach ($danhSachNguoiDung as $nguoiDung) {
            if (($nguoiDung->loainguoidung == 0 || $nguoiDung->loainguoidung == 1) && $nguoiDung->trangthai == 1) {
                $danhSachNhaCungCap = array_merge($danhSachNhaCungCap, [$nguoiDung]);
            }
        }
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.themphieunhap', compact(
            'danhSachSanPham',
            'danhSachHangSanXuatLaptop',
            'danhSachHangSanXuatPhuKien',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachNhaCungCap'
        ));
    }
    public function suaphieunhap(Request $request)
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $rules = [
            'id' => 'required|integer|exists:phieunhap,maphieunhap'
        ];
        $messages = [
            'required' => ':attribute b???t bu???c nh???p',
            'exists' => ':attribute kh??ng t???n t???i',
            'integer' => ':attribute nh???p sai'
        ];
        $attributes = [
            'id' => 'M?? phi???u nh???p'
        ];
        $request->validate($rules, $messages, $attributes);
        $danhSachSanPham = $this->sanPham->layDanhSachSanPham();
        $danhSachHangSanXuat = $this->hangSanXuat->layDanhSachHangSanXuat();
        $danhSachHangSanXuatLaptop = []; // loc lai danh sach theo loai hang san xuat laptop can xem
        $danhSachHangSanXuatPhuKien = [];
        foreach ($danhSachHangSanXuat as $hangSanXuat) {
            if ($hangSanXuat->loaihang == 0) {
                $danhSachHangSanXuatLaptop = array_merge($danhSachHangSanXuatLaptop, [$hangSanXuat]);
            }
            if ($hangSanXuat->loaihang == 1) {
                $danhSachHangSanXuatPhuKien = array_merge($danhSachHangSanXuatPhuKien, [$hangSanXuat]);
            }
        }
        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachNhaCungCap = []; // loc lai danh sach thong tin nha cung cap gom nguoi dung la khach hang hoac doi tac va co trang thai la dang hoat dong
        foreach ($danhSachNguoiDung as $nguoiDung) {
            if (($nguoiDung->loainguoidung == 0 || $nguoiDung->loainguoidung == 1) && $nguoiDung->trangthai == 1) {
                $danhSachNhaCungCap = array_merge($danhSachNhaCungCap, [$nguoiDung]);
            }
        }
        $phieuNhapCanXem = $this->phieuNhap->timPhieuNhapTheoMa($request->id);
        $nguoiDungCanXem = $this->nguoiDung->timNguoiDungTheoMa($phieuNhapCanXem->manguoidung);
        $danhSachChiTietPhieuNhapCanXem = $this->chiTietPhieuNhap->timDanhSachChiTietPhieuNhapTheoMaPhieuNhap($request->id);
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.suaphieunhap', compact(
            'phieuNhapCanXem',
            'nguoiDungCanXem',
            'danhSachChiTietPhieuNhapCanXem',
            'danhSachSanPham',
            'danhSachHangSanXuatLaptop',
            'danhSachHangSanXuatPhuKien',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc',
            'danhSachNhaCungCap'
        ));
    }
    public function magiamgia()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachMaGiamGia = $this->maGiamGia->layDanhSachMaGiamGia();
        $danhSachPhieuXuat = $this->phieuXuat->layDanhSachPhieuXuat();
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.magiamgia', compact(
            'danhSachMaGiamGia',
            'danhSachPhieuXuat',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc'
        ));
    }
    public function xulymagiamgia(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "s???a m?? gi???m gi??") { // *******************************************************************************************sua ma giam gia
            $rules = [
                'maGiamGiaSua' => 'required|string|max:50|min:3|exists:giamgia,magiamgia',
                'ngayBatDauSua' => 'required|date_format:Y-m-d',
                'ngayKetThucSua' => 'required|date_format:Y-m-d|after_or_equal:' . $request->ngayBatDauSua,
                'moTaSua' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'exists' => ':attribute kh??ng t???n t???i',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'date_format' => ':attribute kh??ng ????ng ?????nh d???ng ng??y/th??ng/n??m',
                'ngayKetThucSua.after_or_equal' => 'Ng??y k???t th??c ph???i sau ' . date("d/m/Y", strtotime($request->ngayBatDauSua))
            ];
            $attributes = [
                'maGiamGiaSua' => 'M?? gi???m gi??',
                'ngayBatDauSua' => 'Ng??y b???t ?????u',
                'ngayKetThucSua' => 'Ng??y k???t th??c',
                'moTaSua' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinMaGiamGia = $this->maGiamGia->timMaGiamGiaTheoMa($request->maGiamGiaSua); //tim ma giam gia
            if ($thongTinMaGiamGia->mota != $request->moTaSua) { //so sanh mo ta
                $thongTinMaGiamGia->mota = $request->moTaSua;
            }
            if ($thongTinMaGiamGia->ngaybatdau != $request->ngayBatDauSua) { //so sanh ngay bat dau
                $thongTinMaGiamGia->ngaybatdau = $request->ngayBatDauSua;
            }
            if ($thongTinMaGiamGia->ngayketthuc != $request->ngayKetThucSua) { //so sanh ngay ket thuc
                $thongTinMaGiamGia->ngayketthuc = $request->ngayKetThucSua;
            }
            $dataMaGiamGia = [
                $thongTinMaGiamGia->mota,
                $thongTinMaGiamGia->ngaybatdau,
                $thongTinMaGiamGia->ngayketthuc
            ];
            if (isset($request->hetHanCheck)) {
                if ($request->hetHanCheck == "on") {
                    $dataMaGiamGia = [
                        $thongTinMaGiamGia->mota,
                        date("Y-m-d", strtotime('-2 days')), //hom kia
                        date("Y-m-d", strtotime('-1 days')) //hom qua
                    ];
                } else {
                    return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'Th???i gian ??p d???ng m?? nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
                }
            }
            $this->maGiamGia->suaMaGiamGia($dataMaGiamGia, $thongTinMaGiamGia->magiamgia); //sua ma giam gia tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'S???a m?? gi???m gi?? th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "x??a m?? gi???m gi??") { // *******************************************************************************************xoa ma giam gia
            $rules = [
                'maGiamGiaXoa' => 'required|string|max:50|min:3|exists:giamgia,magiamgia|unique:phieuxuat,magiamgia'
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
                'maGiamGiaXoa' => 'M?? gi???m gi??'
            ];
            $request->validate($rules, $messages, $attributes);
            $this->maGiamGia->xoaMaGiamGia($request->maGiamGiaXoa); //xoa ma giam gia tren database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m m?? gi???m gi?? th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "th??m m?? gi???m gi??") { // *******************************************************************************************them ma giam gia
            $rules = [
                'maGiamGia' => 'required|string|max:50|min:3|unique:giamgia,magiamgia',
                'soTienGiam' => 'required|string|max:255|min:1',
                'ngayBatDau' => 'required|date_format:Y-m-d|after_or_equal:' . date("Y-m-d"),
                'ngayKetThuc' => 'required|date_format:Y-m-d|after_or_equal:' . $request->ngayBatDau,
                'moTa' => 'max:255'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'unique' => ':attribute ???? t???n t???i',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'date_format' => ':attribute kh??ng ????ng ?????nh d???ng ng??y/th??ng/n??m',
                'ngayBatDau.after_or_equal' => 'Ng??y b???t ?????u ph???i sau ' . date("d/m/Y"),
                'ngayKetThuc.after_or_equal' => 'Ng??y k???t th??c ph???i sau ' . date("d/m/Y", strtotime($request->ngayBatDau))
            ];
            $attributes = [
                'maGiamGia' => 'M?? gi???m gi??',
                'soTienGiam' => 'S??? ti???n gi???m',
                'ngayBatDau' => 'Ng??y b???t ?????u',
                'ngayKetThuc' => 'Ng??y k???t th??c',
                'moTa' => 'M?? t???'
            ];
            $request->validate($rules, $messages, $attributes);
            $soTienGiam = explode(',', $request->soTienGiam);
            $temp = "";
            foreach ($soTienGiam as $stg) {
                $temp = $temp . $stg;
            }
            $soTienGiam = $temp;
            if (!is_numeric($soTienGiam)) { // so tien giam nhap vao sai dinh dang, quay lai trang truoc va bao loi
                return back()->with('tieudethongbao', 'Thao t??c th???t b???i')->with('thongbao', 'S??? ti???n gi???m nh???p sai, vui l??ng nh???p l???i!')->with('loaithongbao', 'danger');
            }
            $dataMaGiamGia = [
                $request->maGiamGia, //magiamgia
                $request->moTa,
                $soTienGiam,
                $request->ngayBatDau,
                $request->ngayKetThuc
            ];
            $this->maGiamGia->themMaGiamGia($dataMaGiamGia); //them ma giam gia vao database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m m?? gi???m gi?? th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
    public function nguoidung()
    {
        if (!Auth::check() || Auth::user()->loainguoidung != 2) {
            return redirect()->route('dangnhap');
        }
        $danhSachNguoiDung = $this->nguoiDung->layDanhSachNguoiDung();
        $danhSachPhieuXuatChoXacNhan = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.tinhtranggiaohang', '=', 1]]);
        $danhSachLoiPhanHoiChuaDoc = $this->loiPhanHoi->layDanhSachLoiPhanHoiTheoBoLoc([['loiphanhoi.trangthai', '=', 0]]);
        return view('admin.nguoidung', compact(
            'danhSachNguoiDung',
            'danhSachPhieuXuatChoXacNhan',
            'danhSachLoiPhanHoiChuaDoc'

        ));
    }
    public function xulynguoidung(Request $request)
    {
        $request->validate(['thaoTac' => 'required|string']);
        if ($request->thaoTac == "th??m ng?????i d??ng") { // *******************************************************************************************them nguoi dung
            $rules = [
                'hoTen' => 'required|string|max:50|min:3',
                'soDienThoai' => 'required|numeric|digits:10|unique:nguoidung,sodienthoai',
                'diaChi' => 'required|string|max:255|min:3',
                'loaiNguoiDung' => 'required|integer|between:0,2'
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'string' => ':attribute ???? nh???p sai',
                'integer' => ':attribute ???? nh???p sai',
                'numeric' => ':attribute ???? nh???p sai',
                'unique' => ':attribute ???? t???n t???i',
                'min' => ':attribute t???i thi???u :min k?? t???',
                'max' => ':attribute t???i ??a :max k?? t???',
                'between' => ':attribute v?????t qu?? s??? l?????ng cho ph??p',
                'digits' => ':attribute kh??ng ????ng :digits k?? t???'
            ];
            $attributes = [
                'hoTen' => 'H??? t??n',
                'soDienThoai' => 'S??? ??i???n tho???i',
                'diaChi' => '?????a ch???',
                'loaiNguoiDung' => 'Lo???i ng?????i d??ng'
            ];
            $request->validate($rules, $messages, $attributes);
            $ngayTao = date("Y-m-d H:i:s");
            $dataNguoiDung = [
                NULL, //manguoidung tu tang
                $request->hoTen,
                $request->soDienThoai,
                $request->diaChi,
                1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                $request->loaiNguoiDung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                NULL, //email
                NULL, //matkhau
                $ngayTao
            ];
            if ($request->loaiNguoiDung == 2) {
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
                $dataNguoiDung = [
                    NULL, //manguoidung tu tang
                    $request->hoTen,
                    $request->soDienThoai,
                    $request->diaChi,
                    1, //trangthai 0 la bi khoa, 1 la dang hoat dong
                    $request->loaiNguoiDung, //loainguoidung 0 l?? kh??ch h??ng, 1 l?? ?????i t??c, 2 l?? nh??n vi??n
                    $request->email,
                    bcrypt($request->matKhau),
                    $ngayTao
                ];
            }
            $this->nguoiDung->themNguoiDung($dataNguoiDung); //them nguoi dung vao database
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                'Th??m ng?????i d??ng th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        if ($request->thaoTac == "?????i tr???ng th??i ng?????i d??ng") { // *******************************************************************************************doi trang thai nguoi dung
            $rules = [
                'maNguoiDungKhoa' => 'required|integer|exists:nguoidung,manguoidung',
            ];
            $messages = [
                'required' => ':attribute b???t bu???c nh???p',
                'exists' => ':attribute kh??ng t???n t???i',
                'integer' => ':attribute nh???p sai'
            ];
            $attributes = [
                'maNguoiDungKhoa' => 'M?? ng?????i d??ng'
            ];
            $request->validate($rules, $messages, $attributes);
            $thongTinNguoiDung = $this->nguoiDung->timNguoiDungTheoMa($request->maNguoiDungKhoa); // tim ng?????i d??ng
            // ***********Xu ly khoa nguoi dung
            if ($thongTinNguoiDung->trangthai == 0) { // so s??nh tr???ng th??i 0: b??? kh??a || 1: ho???t ?????ng
                $thongTinNguoiDung->trangthai = 1;
            } else if ($thongTinNguoiDung->trangthai == 1) {
                $danhSachPhieuXuat = $this->phieuXuat->layDanhSachPhieuXuatTheoBoLoc([['phieuxuat.manguoidung', '=', $thongTinNguoiDung->manguoidung]]);
                foreach ($danhSachPhieuXuat as $px) {
                    if ($px->tinhtranggiaohang > 0 && $px->tinhtranggiaohang < 4) { // 1 la cho xac nhan //2 la dang chuan bi hang //3 la dang giao hang
                        $dataPhieuXuat = [
                            0 //chuyen het lai thanh da huy
                        ];
                        $this->phieuXuat->doiTinhTrangGiaoHangPhieuXuat($dataPhieuXuat, $px->maphieuxuat); //doi tinh trang giao hang phieu xuat tren database
                    }
                }
                $thongTinNguoiDung->trangthai = 0;
            }
            $dataNguoiDung = [
                $thongTinNguoiDung->trangthai
            ];
            $this->nguoiDung->doiTrangThaiNguoiDung($dataNguoiDung, $thongTinNguoiDung->manguoidung);
            return back()->with(
                'tieudethongbao',
                'Thao t??c th??nh c??ng'
            )->with(
                'thongbao',
                '?????i tr???ng th??i ng?????i d??ng th??nh c??ng'
            )->with(
                'loaithongbao',
                'success'
            );
        }
        return back()->with(
            'tieudethongbao',
            'Thao t??c th???t b???i'
        )->with(
            'thongbao',
            'Vui l??ng th??? l???i!'
        )->with(
            'loaithongbao',
            'danger'
        );
    }
}
