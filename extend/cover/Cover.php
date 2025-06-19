<?php

namespace cover;

class Cover
{
    // 配置项
    protected static $config = [
        'width' => 658,          // 封面宽度
        'height' => 940,         // 封面高度
        'bg_image' => 'public/static/home/images/default_cover_bg.jpg', // 背景图路径
        'logo' => 'public/static/home/images/logo.png',   // logo路径
        'title_line_height' => 3, // 标题行距倍数（基于字体大小）
        'min_line_height' => 40,    // 最小行距(像素)
        'font' => 'public/static/home/font/SourceHanSansCN-Heavy.otf',       // 字体文件
        'authorfont' => 'public/static/home/font/Alibaba-PuHuiTi-Regular.otf', // 字体文件路径作者
    ];

    /**
     * 生成封面图
     * @param string $title  小说标题
     * @param string $author 作者名称
     * @param string $output 输出路径
     */
    public static function generate($title, $author, $output = '')
    {
        // 创建画布
        $image = imagecreatetruecolor(self::$config['width'], self::$config['height']);
        self::$config['site_name'] = get_system_config('web', 'title');
        // 填充背景
        if (file_exists(app()->getRootPath() . self::$config['bg_image'])) {
            $bg = imagecreatefromjpeg(app()->getRootPath() . self::$config['bg_image']);
            imagecopy($image, $bg, 0, 0, 0, 0, self::$config['width'], self::$config['height']);
            imagedestroy($bg);
        } else {
            $bgColor = imagecolorallocate($image, 240, 240, 240);
            imagefill($image, 0, 0, $bgColor);
        }

        // 添加logo
        if (file_exists(app()->getRootPath() . self::$config['logo'])) {
            $logo = imagecreatefrompng(app()->getRootPath() . self::$config['logo']);
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);
            $logoX = (self::$config['width'] - $logoWidth) / 2;
            $logoY = 50;
            imagecopy($image, $logo, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);
            imagedestroy($logo);
        }

        // 设置颜色
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $shadowColor = imagecolorallocate($image, 100, 100, 100);

        // 添加小说标题（带自动换行）
        $titleFontSize = 28;
        $titleMaxWidth = self::$config['width'] - 100;
        $titleY = 350;

        // 计算实际行高（取字体大小×行距倍数和最小行距中的较大值）
        $lineHeight = (int) max(
            $titleFontSize * self::$config['title_line_height'],
            self::$config['min_line_height']
        );

        $lines = self::wrapText($title, $titleFontSize, $titleMaxWidth);

        foreach ($lines as $i => $line) {
            $titleBox = imagettfbbox($titleFontSize, 0, app()->getRootPath() . self::$config['font'], $line);
            $titleWidth = $titleBox[2] - $titleBox[0];
            $titleX = (int) ((self::$config['width'] - $titleWidth) / 2);

            // 使用计算出的行高控制垂直间距
            $currentY = (int) ($titleY + ($i * $lineHeight));

            // 文字阴影
            imagettftext($image, $titleFontSize, 0, $titleX + 2, $currentY + 2, $shadowColor, app()->getRootPath() . self::$config['font'], $line);
            // 文字主体
            imagettftext($image, $titleFontSize, 0, $titleX, $currentY, $textColor, app()->getRootPath() . self::$config['font'], $line);
        }

        // 添加作者名称（自适应字号）
        $authorText = lang('author') . ": " . $author;
        $authorY = (int) ($titleY + count($lines) * $lineHeight + 150);

        // 计算作者名称所需字号
        $authorFontSize = self::calculateFontSize(
            $authorText,
            self::$config['width'] - 100, // 最大允许宽度
            20, // 初始字号
            12  // 最小字号
        );

        // 渲染作者名称
        $authorBox = imagettfbbox($authorFontSize, 0, app()->getRootPath() . self::$config['authorfont'], $authorText);
        $authorWidth = $authorBox[2] - $authorBox[0];
        $authorX = (int) ((self::$config['width'] - $authorWidth) / 2);

        // 主体文字
        imagettftext($image, $authorFontSize, 0, $authorX, $authorY, $textColor, app()->getRootPath() . self::$config['authorfont'], $authorText);

        // 添加网站名称
        $siteFontSize = 16;
        $siteBox = imagettfbbox($siteFontSize, 0, app()->getRootPath() . self::$config['authorfont'], self::$config['site_name']);
        $siteWidth = $siteBox[2] - $siteBox[0];
        $siteX = (int) ((self::$config['width'] - $siteWidth) / 2);
        $siteY = (int) (self::$config['height'] - 50);

        imagettftext($image, $siteFontSize, 0, $siteX, $siteY, $textColor, app()->getRootPath() . self::$config['authorfont'], self::$config['site_name']);

        // 输出图像
        if (empty($output)) {
            $output =  get_config('filesystem.disks.public.root') . '/cover/' . md5($title . $author) . '.jpg';
        }

        $dir = dirname($output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagejpeg($image, $output, 90);
        imagedestroy($image);

        return $output;
    }

    /**
     * 计算适合的字号使文本在指定宽度内显示
     * @param string $text 文本内容
     * @param int $maxWidth 最大允许宽度
     * @param int $initialSize 初始字号
     * @param int $minSize 最小字号
     * @return int 合适的字号
     */
    protected static function calculateFontSize($text, $maxWidth, $initialSize = 20, $minSize = 12)
    {
        $fontSize = $initialSize;

        do {
            $box = imagettfbbox($fontSize, 0, app()->getRootPath() . self::$config['font'], $text);
            $textWidth = $box[2] - $box[0];

            if ($textWidth <= $maxWidth || $fontSize <= $minSize) {
                break;
            }

            $fontSize--; // 逐步减小字号
        } while (true);

        return max($fontSize, $minSize); // 确保不小于最小字号
    }

    /**
     * 文字自动换行处理
     * @param string $text 文本内容
     * @param int $fontSize 字体大小
     * @param int $maxWidth 最大宽度
     * @return array 分行后的文本数组
     */
    protected static function wrapText($text, $fontSize, $maxWidth)
    {
        $lines = [];
        $words = preg_split('/(?<!^)(?!$)/u', $text); // 支持中文分词

        $line = '';
        foreach ($words as $word) {
            $testLine = $line . $word;
            $box = imagettfbbox($fontSize, 0, app()->getRootPath() . self::$config['font'], $testLine);
            $testWidth = $box[2] - $box[0];

            if ($testWidth > $maxWidth && !empty($line)) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $testLine;
            }
        }

        if (!empty($line)) {
            $lines[] = $line;
        }

        return $lines;
    }
}
