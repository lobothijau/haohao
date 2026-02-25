export type BlogCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
};

export type BlogPost = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    featured_image_url: string | null;
    blog_category_id: number | null;
    is_published: boolean;
    published_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
    category: BlogCategory | null;
    creator: { id: number; name: string } | null;
};

export type SeoMeta = {
    title: string;
    description: string | null;
    image: string | null;
    url: string;
    type: string;
    published_time: string | null;
    author: string | null;
};
