export default function StatCard({ data }) {
  const Icon = data.icon;

  return (
    <div className="bg-white rounded-2xl p-5 shadow-md
            transition-all duration-300 hover:shadow-lg duration-300 flex justify-between items-center">

      <div>
        <p className="text-gray-500 text-sm">
          {data.title}
        </p>

        <h2 className="text-2xl font-bold mt-1">
          {data.value}
        </h2>
      </div>

      <div className={`${data.color} p-3 rounded-xl text-white`}>
        <Icon size={24} />
      </div>
    </div>
  );
}