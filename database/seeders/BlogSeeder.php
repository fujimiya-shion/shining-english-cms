<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $hasShortDescription = Schema::hasColumn('blogs', 'short_description');
        $hasThumbnail = Schema::hasColumn('blogs', 'thumbnail');
        $hasContent = Schema::hasColumn('blogs', 'content');

        $tags = collect([
            ['name' => 'Tự học giao tiếp', 'slug' => 'tu-hoc-giao-tiep'],
            ['name' => 'Phát âm', 'slug' => 'phat-am'],
            ['name' => 'Ngữ pháp ứng dụng', 'slug' => 'ngu-phap-ung-dung'],
            ['name' => 'Từ vựng theo chủ đề', 'slug' => 'tu-vung-theo-chu-de'],
            ['name' => 'Luyện thi', 'slug' => 'luyen-thi'],
        ])->mapWithKeys(function (array $tag): array {
            $record = BlogTag::query()->updateOrCreate(
                ['slug' => $tag['slug']],
                ['name' => $tag['name']],
            );

            return [$tag['slug'] => $record];
        });

        $blogs = [
            [
                'title' => 'Shadowing 15 phút mỗi ngày để nói tiếng Anh tự nhiên hơn',
                'slug' => 'shadowing-15-phut-moi-ngay-de-noi-tieng-anh-tu-nhien-hon',
                'description' => 'Một routine ngắn để luyện nghe - nhại - sửa nhịp nói, phù hợp cho người đi làm bận rộn.',
                'short_description' => 'Routine ngắn để luyện shadowing 15 phút/ngày, phù hợp người đi làm bận rộn.',
                'thumbnail' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Vì sao shadowing hiệu quả</h2>
<p>Shadowing không chỉ là nhại lại âm thanh. Khi làm đúng, bạn đang luyện cùng lúc nhịp câu, trọng âm, nối âm và phản xạ miệng. Người học giao tiếp thường nghe được khá nhiều nhưng vẫn nói cứng, vì tai hiểu mà cơ miệng chưa quen.</p>
<p>Mỗi buổi chỉ cần chọn một đoạn audio dài 30 đến 60 giây. Nghe một lượt để nắm ý, nghe lượt hai để đánh dấu cụm từ khó, sau đó bật chậm và nói đè theo speaker. Mục tiêu ban đầu không phải giống 100%, mà là giữ được nhịp câu và không bỏ âm cuối.</p>
<h2>Routine 15 phút</h2>
<ul>
<li>3 phút nghe và chép keyword.</li>
<li>5 phút shadowing tốc độ chậm.</li>
<li>5 phút shadowing tốc độ thật.</li>
<li>2 phút tự thu âm và nghe lại.</li>
</ul>
<p>Nếu chỉ có một việc cần theo dõi, hãy theo dõi số lần bạn bỏ âm cuối hoặc nuốt mất trọng âm chính của câu. Đó là hai lỗi thấy rõ nhất ở người học Việt Nam khi luyện giao tiếp.</p>
<h2>Cách chọn tài liệu</h2>
<p>Ưu tiên video hội thoại ngắn, podcast có transcript hoặc lesson clip từ giáo viên bản ngữ nói rõ ràng. Tránh bắt đầu bằng TED Talk dài hoặc phim tốc độ quá nhanh, vì bạn sẽ dễ chuyển từ luyện phát âm sang chỉ cố gắng sống sót với nội dung.</p>
HTML,
                'required_star' => 0,
                'status' => true,
                'tag_slug' => 'tu-hoc-giao-tiep',
            ],
            [
                'title' => 'Checklist sửa âm cuối /t/, /d/, /s/, /z/ cho người Việt',
                'slug' => 'checklist-sua-am-cuoi-t-d-s-z-cho-nguoi-viet',
                'description' => 'Bộ kiểm nhanh để biết mình đang rơi vào lỗi phát âm nào và cách sửa theo từng bước.',
                'short_description' => 'Bộ checklist phát âm âm cuối để tự kiểm và sửa lỗi khi nói nhanh.',
                'thumbnail' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Lỗi phổ biến nhất không phải là đọc sai, mà là không đọc</h2>
<p>Nhiều người học biết từ <em>worked</em>, <em>bags</em> hay <em>missed</em> có âm cuối, nhưng khi nói nhanh lại bỏ hẳn. Kết quả là người nghe mất thông tin về thì, số nhiều hoặc dạng từ.</p>
<h2>Checklist tự kiểm</h2>
<ol>
<li>Miệng có khép hoặc chạm đúng vị trí kết thúc âm không?</li>
<li>Bạn có cố bật mạnh âm cuối thành một âm tiết riêng không?</li>
<li>Khi nối sang từ tiếp theo, âm cuối có còn giữ được không?</li>
</ol>
<p>Ví dụ với /t/ trong <strong>last time</strong>, bạn không cần bật mạnh thành “las-tờ”. Chỉ cần chặn luồng hơi ở đầu lưỡi rồi chuyển sang phụ âm đầu của từ tiếp theo. Cảm giác đúng là ngắn, gọn, có khóa âm.</p>
<h2>Mini drill 5 phút</h2>
<p>Đọc theo cặp: <em>play - played</em>, <em>work - worked</em>, <em>bag - bags</em>, <em>rice - rise</em>. Thu âm một lượt chậm, một lượt tốc độ nói tự nhiên. Nghe lại và đánh dấu những từ mà âm cuối mất hẳn.</p>
HTML,
                'required_star' => 0,
                'status' => true,
                'tag_slug' => 'phat-am',
            ],
            [
                'title' => '3 mẫu câu small talk giúp bắt đầu cuộc nói chuyện đỡ gượng',
                'slug' => '3-mau-cau-small-talk-giup-bat-dau-cuoc-noi-chuyen-do-guong',
                'description' => 'Không cần nói hay ngay từ đầu. Chỉ cần mở đúng nhịp để người đối diện muốn tiếp tục.',
                'short_description' => '3 mẫu câu small talk đơn giản để mở đầu cuộc trò chuyện tự nhiên hơn.',
                'thumbnail' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Small talk không phải nói cho nhiều</h2>
<p>Điểm yếu lớn nhất khi bắt chuyện bằng tiếng Anh là người học cố nghĩ một câu thật hay. Thực tế, small talk hiệu quả lại rất đơn giản: quan sát đúng ngữ cảnh, hỏi câu mở, rồi follow-up một chi tiết nhỏ.</p>
<h2>Ba mẫu câu dễ dùng</h2>
<ul>
<li><strong>How do you know the host?</strong> dùng cho workshop, event, meetup.</li>
<li><strong>Have you tried this before?</strong> dùng cho món ăn, hoạt động, công cụ mới.</li>
<li><strong>What’s been the best part so far?</strong> dùng khi cuộc gặp đã diễn ra được một lúc.</li>
</ul>
<p>Sau câu mở, hãy bám theo danh từ hoặc trải nghiệm người kia vừa nhắc tới. Ví dụ nếu họ nói làm trong HR, đừng nhảy sang kể về bản thân ngay. Hỏi thêm: <em>What kind of hiring are you focused on these days?</em></p>
<p>Điểm mấu chốt là tốc độ vừa phải, câu ngắn, ánh mắt bình tĩnh. Người nghe đánh giá sự dễ chịu nhiều hơn độ phức tạp của từ vựng.</p>
HTML,
                'required_star' => 0,
                'status' => true,
                'tag_slug' => 'tu-hoc-giao-tiep',
            ],
            [
                'title' => 'Roadmap 6 tuần kéo IELTS Writing Task 2 từ bí ý lên bài có cấu trúc',
                'slug' => 'roadmap-6-tuan-keo-ielts-writing-task-2-tu-bi-y-len-bai-co-cau-truc',
                'description' => 'Kế hoạch luyện viết theo tuần, tập trung vào dàn ý, luận điểm và sửa lỗi lặp.',
                'short_description' => 'Roadmap 6 tuần để viết Task 2 có khung, có luận điểm và ít lặp ý.',
                'thumbnail' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Tuần 1: bỏ thói quen viết ngay</h2>
<p>Nhiều bài Writing Task 2 yếu không phải vì grammar kém, mà vì không có khung nghĩ. Một đề opinion hoặc discussion nếu lao vào viết luôn sẽ rất dễ bị lặp ý, thân bài loãng và kết luận mờ.</p>
<h2>Khung 6 tuần</h2>
<ul>
<li>Tuần 1: phân loại đề và tập outline trong 10 phút.</li>
<li>Tuần 2: viết topic sentence và supporting idea rõ nguyên nhân - hệ quả.</li>
<li>Tuần 3: luyện paraphrase mở bài và thesis statement.</li>
<li>Tuần 4: sửa coherence bằng linking logic thay vì nhồi connectors.</li>
<li>Tuần 5: chữa lỗi ngữ pháp lặp lại theo checklist cá nhân.</li>
<li>Tuần 6: làm full test 40 phút và tự review.</li>
</ul>
<p>Mỗi tuần chỉ cần 3 bài nhưng phải review kỹ. Nếu không có thời gian viết nhiều, hãy ưu tiên viết ít hơn nhưng outline và tự chữa sâu hơn.</p>
<h2>Điểm đáng tiền nhất</h2>
<p>Người học thường sợ thiếu từ vựng band cao, trong khi vấn đề thật là mỗi đoạn thân bài chưa có một luận điểm đủ cụ thể. Khi bạn viết rõ “vì sao” và “hậu quả là gì”, band sẽ tăng bền hơn nhiều so với việc cố nhét từ hiếm.</p>
HTML,
                'required_star' => 8,
                'status' => true,
                'tag_slug' => 'luyen-thi',
            ],
            [
                'title' => 'Bộ từ vựng đi bệnh viện và mô tả triệu chứng không bị dịch từng chữ',
                'slug' => 'bo-tu-vung-di-benh-vien-va-mo-ta-trieu-chung-khong-bi-dich-tung-chu',
                'description' => 'Từ vựng theo tình huống thật: đặt lịch, mô tả đau ở đâu, nói về mức độ và thời gian.',
                'short_description' => 'Từ vựng y tế theo cụm để mô tả triệu chứng nhanh và đúng ngữ cảnh.',
                'thumbnail' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Đừng học từng từ rời</h2>
<p>Khi cần nói với bác sĩ hoặc nhân viên y tế, bạn không có thời gian ghép từng từ một. Cách học hiệu quả hơn là đi theo cụm: <em>I’ve been having...</em>, <em>The pain gets worse when...</em>, <em>It started about three days ago.</em></p>
<h2>Ba nhóm câu cần nhớ</h2>
<ul>
<li><strong>Mô tả triệu chứng:</strong> sore throat, dizziness, shortness of breath, nausea.</li>
<li><strong>Nói về thời gian:</strong> since yesterday, for a week, on and off.</li>
<li><strong>Nói về mức độ:</strong> mild, sharp, constant, unbearable.</li>
</ul>
<p>Ví dụ thực chiến: <em>I’ve had a sharp pain in my lower back since Monday, and it gets worse when I bend down.</em> Một câu như vậy rõ hơn rất nhiều so với việc chỉ nói “back pain”.</p>
<h2>Cách luyện</h2>
<p>Viết 5 tình huống ngắn theo đời sống của bạn hoặc gia đình, rồi đọc thành tiếng. Mục tiêu là bật ra được câu mô tả hoàn chỉnh trong 2 đến 3 giây, không dừng ở giữa để tìm từ.</p>
HTML,
                'required_star' => 5,
                'status' => true,
                'tag_slug' => 'tu-vung-theo-chu-de',
            ],
            [
                'title' => 'Present Perfect trong giao tiếp: khi nào dùng, khi nào nên tránh',
                'slug' => 'present-perfect-trong-giao-tiep-khi-nao-dung-khi-nao-nen-tranh',
                'description' => 'Tập trung vào tình huống nói thật để bớt nhầm giữa present perfect và past simple.',
                'short_description' => 'Phân biệt Present Perfect và Past Simple bằng tình huống giao tiếp thật.',
                'thumbnail' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Present Perfect không khó vì cấu trúc, mà khó vì ngữ cảnh</h2>
<p>Người học thường nhớ công thức <em>have/has + V3</em> nhưng vẫn dùng sai vì không rõ khi nào cần nhấn vào trải nghiệm hoặc kết quả ở hiện tại. Trong giao tiếp, chỉ cần nắm đúng ba tình huống là dùng ổn.</p>
<h2>Ba tình huống nên dùng</h2>
<ul>
<li>Kinh nghiệm: <em>I’ve tried that app before.</em></li>
<li>Kết quả hiện tại: <em>I’ve lost my keys.</em></li>
<li>Việc bắt đầu trong quá khứ và còn kéo dài: <em>I’ve worked here for three years.</em></li>
</ul>
<p>Nếu trong câu có mốc thời gian đã chốt như <em>yesterday</em>, <em>last year</em>, <em>in 2023</em>, thì thường nên quay về past simple. Đây là điểm mà người học hay nhầm nhất khi vừa ôn grammar vừa cố áp dụng vào nói.</p>
<h2>Cách luyện nhanh</h2>
<p>Thay vì làm 20 câu trắc nghiệm, hãy tự nói 10 câu về đời mình: trải nghiệm, công việc, thói quen mới, thứ vừa hoàn thành. Sau đó gạch chân những câu có mốc thời gian cụ thể để kiểm tra xem bạn có đang dùng sai thì không.</p>
HTML,
                'required_star' => 6,
                'status' => true,
                'tag_slug' => 'ngu-phap-ung-dung',
            ],
            [
                'title' => 'Template trả lời Speaking Part 1 mà không nghe như học thuộc',
                'slug' => 'template-tra-loi-speaking-part-1-ma-khong-nghe-nhu-hoc-thuoc',
                'description' => 'Một framework ngắn giúp câu trả lời có nhịp và có chi tiết, nhưng vẫn tự nhiên.',
                'short_description' => 'Template trả lời Speaking Part 1 ngắn, có nhịp và không bị học thuộc.',
                'thumbnail' => 'https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Speaking Part 1 cần gọn, không cần diễn văn</h2>
<p>Nhiều thí sinh trả lời quá cụt nên thiếu điểm phát triển ý, hoặc trả lời quá dài nên rối. Framework dễ dùng nhất là: trả lời trực tiếp, thêm một lý do, rồi chốt bằng một ví dụ nhỏ hoặc thói quen cá nhân.</p>
<h2>Một khung an toàn</h2>
<p><strong>Yes, I do.</strong> I usually ... because ... <strong>For example,</strong> ...</p>
<p>Ví dụ với chủ đề books: <em>Yes, I do. I usually read short non-fiction books because they help me learn something practical without taking too much time. For example, I often read one chapter before going to bed.</em></p>
<h2>Điểm cần tránh</h2>
<ul>
<li>Mở đầu bằng câu quá máy móc như <em>Well, it depends</em> cho mọi câu hỏi.</li>
<li>Lặp lại nguyên từ trong đề quá nhiều lần.</li>
<li>Nói dài nhưng không có thông tin mới.</li>
</ul>
<p>Muốn nghe tự nhiên hơn, hãy chuẩn bị sẵn 8 đến 10 mảnh nội dung cá nhân có thể tái sử dụng: giờ giấc làm việc, thói quen cuối tuần, một quán quen, một ứng dụng hay dùng, một kỹ năng đang học.</p>
HTML,
                'required_star' => 9,
                'status' => true,
                'tag_slug' => 'luyen-thi',
            ],
            [
                'title' => '10 collocations công sở dùng được ngay trong email và họp online',
                'slug' => '10-collocations-cong-so-dung-duoc-ngay-trong-email-va-hop-online',
                'description' => 'Tập trung vào các cụm thực dụng, không màu mè, giúp nói và viết chuyên nghiệp hơn.',
                'short_description' => '10 collocations công sở thực dụng để dùng ngay trong email và họp.',
                'thumbnail' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'HTML'
<h2>Học từ riêng lẻ khiến câu bị cứng</h2>
<p>Trong môi trường công sở, điều cần không phải là những từ quá học thuật, mà là cụm tự nhiên và dùng lặp lại được. Nói <em>make a decision</em>, <em>meet a deadline</em>, <em>raise a concern</em> sẽ ổn hơn nhiều so với việc ghép từ từng chữ theo kiểu dịch tiếng Việt.</p>
<h2>10 collocations nên dùng</h2>
<ul>
<li>meet a deadline</li>
<li>raise a concern</li>
<li>share an update</li>
<li>align on priorities</li>
<li>follow up on something</li>
<li>address an issue</li>
<li>set expectations</li>
<li>clarify the scope</li>
<li>review the timeline</li>
<li>move things forward</li>
</ul>
<p>Cách học hiệu quả là đặt từng cụm vào một câu đúng bối cảnh công việc của bạn. Nếu làm product, sales, HR hay ops thì ví dụ phải khác nhau. Khi ví dụ bám việc thật, bạn sẽ nhớ rất nhanh và dùng được ngay.</p>
HTML,
                'required_star' => 0,
                'status' => true,
                'tag_slug' => 'tu-vung-theo-chu-de',
            ],
        ];

        foreach ($blogs as $blog) {
            $tag = $tags->get($blog['tag_slug']);

            $payload = [
                'title' => $blog['title'],
                'description' => $blog['description'],
                'status' => $blog['status'],
                'required_star' => $blog['required_star'],
                'tag_id' => $tag?->id,
            ];

            if ($hasShortDescription) {
                $payload['short_description'] = $blog['short_description'] ?? null;
            }

            if ($hasThumbnail) {
                $payload['thumbnail'] = $blog['thumbnail'];
            }

            if ($hasContent) {
                $payload['content'] = $blog['content'];
            }

            Blog::query()->updateOrCreate(
                ['slug' => $blog['slug']],
                $payload,
            );
        }
    }
}
