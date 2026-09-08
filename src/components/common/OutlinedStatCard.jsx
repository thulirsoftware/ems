export default function OutlinedStatCard({ title, value, icon: Icon }) {
    return (
        <>
            <div
                className="
                bg-white 
                rounded-xl 
                border border-purple-200 
                shadow-sm 
                p-5 
                flex items-center justify-between 
                gap-4 
                transition-all duration-300
                hover:shadow-md
                w-full
                min-h-[110px]
            "
                style={{
                    borderLeft: "6px solid #6b2fa0",
                }}
            >
                {/* Text Section */}
                <div className="flex flex-col">
                    <p className="text-sm text-gray-500">{title}</p>
                    <h2 className="text-2xl font-semibold text-gray-900 mt-1">
                        {value}
                    </h2>
                </div>

                {/* Icon */}
                <div
                    className="
                    min-w-[44px] min-h-[44px]
                    rounded-full 
                    bg-purple-50 
                    flex items-center justify-center
                    shrink-0
                "
                >
                    <Icon className="w-5 h-5 text-purple-700" />
                </div>
            </div></>

    );
}
